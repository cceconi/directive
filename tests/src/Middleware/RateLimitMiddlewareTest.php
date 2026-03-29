<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Middleware\RateLimitMiddleware;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\Business\ErrorManager;
use Directive\Service\Configuration\AbstractFeatures;
use Directive\Service\Configuration\DirectiveFeatures;
use Directive\Http\Validator\NullRequestValidator;
use Directive\Service\RateLimit\DefaultRateLimitConfig;
use Directive\Service\RateLimit\NullRateLimiter;
use Directive\Service\RateLimit\RateLimitConfigInterface;
use Directive\Service\RateLimit\RateLimitResult;
use Directive\Service\RateLimit\RateLimiterInterface;
use Directive\Service\Security\WebUserInterface;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubWebUser;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a minimal DI container for the middleware.
 *
 * @param array<string, mixed> $overrides
 */
function buildRlContainer(
    ApiDefinitionManager $manager,
    array $overrides = [],
): Psr\Container\ContainerInterface {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(array_merge(
        [
            ApiDefinitionManager::class   => $manager,
            AbstractFeatures::class       => new DirectiveFeatures(),
            RateLimitConfigInterface::class => new DefaultRateLimitConfig(),
            RateLimiterInterface::class   => new NullRateLimiter(),
            WebUserInterface::class       => new StubWebUser(),
        ],
        $overrides,
    ));

    return $builder->build();
}

/**
 * A PSR-15 handler that always returns 200.
 */
function okHandler(): RequestHandlerInterface
{
    return new class implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return new Response(200);
        }
    };
}

/**
 * Build a minimal ApiDefinitionManager with a single GET /d/v1/svc/res endpoint.
 *
 * @param array<string, mixed> $withMethods
 */
function buildRlManager(array $withMethods = []): ApiDefinitionManager
{
    $manager = new ApiDefinitionManager();
    $domain  = new Domain('d');

    $resource = $domain->version('v1')
        ->service('svc')
        ->resource('res')
        ->withErrorClass(ErrorManager::class)
        ->withRequestValidatorClass(NullRequestValidator::class);

    foreach ($withMethods as $method) {
        $resource->addMethod($method, StubApi::class);
    }

    if ($withMethods === []) {
        $resource->get(StubApi::class);
    }

    $manager->registerDomain($domain);

    return $manager;
}

/**
 * A RateLimiterInterface stub that rejects immediately.
 */
final class RejectingLimiter implements RateLimiterInterface
{
    public function check(string $key, RateLimit $limit): RateLimitResult
    {
        return RateLimitResult::rejected(30);
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('RateLimitMiddleware', function (): void {

    it('passes through for paths with fewer than 4 segments', function (): void {
        $container  = buildRlContainer(buildRlManager());
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/health/live');

        $response = $middleware->process($request, okHandler());

        expect($response->getStatusCode())->toBe(200);
    });

    it('passes through for routes not registered in the ApiDefinitionManager', function (): void {
        $container  = buildRlContainer(buildRlManager());
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/unknown/v1/svc/res');

        $response = $middleware->process($request, okHandler());

        expect($response->getStatusCode())->toBe(200);
    });

    it('adds X-RateLimit-* headers to allowed responses', function (): void {
        $container  = buildRlContainer(buildRlManager());
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/d/v1/svc/res', [], null, '1.1', ['REMOTE_ADDR' => '127.0.0.1']);

        $response = $middleware->process($request, okHandler());

        expect($response->getStatusCode())->toBe(200);
        expect($response->getHeaderLine('X-RateLimit-Limit'))->not->toBeEmpty();
        expect($response->getHeaderLine('X-RateLimit-Remaining'))->not->toBeEmpty();
    });

    it('returns 429 when rate limit is exceeded', function (): void {
        $container  = buildRlContainer(buildRlManager(), [
            RateLimiterInterface::class => new RejectingLimiter(),
        ]);
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/d/v1/svc/res', [], null, '1.1', ['REMOTE_ADDR' => '10.0.0.1']);

        $response = $middleware->process($request, okHandler());

        expect($response->getStatusCode())->toBe(429);
        expect($response->getHeaderLine('Retry-After'))->toBe('30');
        expect($response->getHeaderLine('X-RateLimit-Remaining'))->toBe('0');
    });

    it('skips rate limiting when rateLimitEnabled is false on Method', function (): void {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('d');
        $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withRateLimitEnabled(false)
            ->get(StubApi::class);
        $manager->registerDomain($domain);

        $container  = buildRlContainer($manager, [
            RateLimiterInterface::class => new RejectingLimiter(),
        ]);
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/d/v1/svc/res', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);

        $response = $middleware->process($request, okHandler());

        // Despite rejecting limiter, response must be 200 (disabled at method level)
        expect($response->getStatusCode())->toBe(200);
    });

    it('skips rate limiting when global feature flag is disabled', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_ENABLED'] = '0';

        $container  = buildRlContainer(buildRlManager(), [
            AbstractFeatures::class     => new DirectiveFeatures(),
            RateLimiterInterface::class => new RejectingLimiter(),
        ]);
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/d/v1/svc/res', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);

        $response = $middleware->process($request, okHandler());

        unset($_ENV['DIRECTIVE_RATE_LIMIT_ENABLED']);

        expect($response->getStatusCode())->toBe(200);
    });

    it('enforces rate limiting when rateLimitEnabled is true even if global feature is disabled', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_ENABLED'] = '0';

        $manager = new ApiDefinitionManager();
        $domain  = new Domain('d');
        $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withRateLimitEnabled(true)
            ->get(StubApi::class);
        $manager->registerDomain($domain);

        $container  = buildRlContainer($manager, [
            AbstractFeatures::class     => new DirectiveFeatures(),
            RateLimiterInterface::class => new RejectingLimiter(),
        ]);
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/d/v1/svc/res', [], null, '1.1', ['REMOTE_ADDR' => '1.2.3.4']);

        $response = $middleware->process($request, okHandler());

        unset($_ENV['DIRECTIVE_RATE_LIMIT_ENABLED']);

        // Force-enabled per route overrides the global disabled flag → RejectingLimiter fires → 429
        expect($response->getStatusCode())->toBe(429);
    });

    it('uses X-Forwarded-For IP when present', function (): void {
        $container  = buildRlContainer(buildRlManager());
        $middleware = new RateLimitMiddleware($container);
        $request    = (new ServerRequest('GET', '/d/v1/svc/res'))
            ->withHeader('X-Forwarded-For', '203.0.113.1, 10.0.0.1');

        $response = $middleware->process($request, okHandler());

        // Should pass without 429 (NullRateLimiter)
        expect($response->getStatusCode())->toBe(200);
    });

    it('uses per-method RateLimit when set', function (): void {
        $customLimit = new RateLimit(window: 3600, maxRequests: 5, keyType: RateLimitKeyType::Ip);

        $manager = new ApiDefinitionManager();
        $domain  = new Domain('d');
        $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withRateLimit($customLimit)
            ->get(StubApi::class);
        $manager->registerDomain($domain);

        $container  = buildRlContainer($manager);
        $middleware = new RateLimitMiddleware($container);
        $request    = new ServerRequest('GET', '/d/v1/svc/res', [], null, '1.1', ['REMOTE_ADDR' => '9.9.9.9']);

        $response = $middleware->process($request, okHandler());

        // NullRateLimiter always allows; X-RateLimit-Limit should reflect the per-method limit
        expect($response->getStatusCode())->toBe(200);
        expect($response->getHeaderLine('X-RateLimit-Limit'))->toBe('5');
    });
});
