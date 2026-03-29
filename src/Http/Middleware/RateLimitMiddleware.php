<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\Configuration\AbstractFeatures;
use Directive\Service\RateLimit\RateLimitConfigInterface;
use Directive\Service\RateLimit\RateLimiterInterface;
use Directive\Service\Security\WebUserInterface;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware that enforces per-route rate limits.
 *
 * Position in the Slim LIFO stack: added before AuthMiddleware in code,
 * therefore executes AFTER AuthMiddleware in the request processing chain.
 *
 * Route info is resolved by parsing the URI path directly because Slim's
 * RoutingMiddleware has not yet run at this point (it is innermost).
 *
 * On rejection: returns HTTP 429 with Retry-After and X-RateLimit-* headers.
 * On success:   forwards to the next handler and attaches X-RateLimit-* headers.
 *
 * Fail-open: any service resolution or look-up error transparently passes through.
 */
final class RateLimitMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        // 1. Parse path segments — expect /{domain}/{version}/{service}/{resource}
        $path  = $request->getUri()->getPath();
        $parts = array_values(array_filter(explode('/', $path)));

        if (count($parts) < 4) {
            return $handler->handle($request);
        }

        [$domain, $version, $service, $resource] = $parts;
        $httpMethod = $request->getMethod();

        // 2. Resolve Method from the registered API tree (fail-open on unknown routes)
        try {
            /** @var ApiDefinitionManager $manager */
            $manager = $this->container->get(ApiDefinitionManager::class);
            $method  = $manager
                ->findDomain($domain)
                ->findVersion($version)
                ->findService($service)
                ->findResource($resource)
                ->findMethod($httpMethod);
        } catch (\Throwable) {
            return $handler->handle($request);
        }

        // 3. Check per-method toggle (explicit false = disabled for this method)
        if ($method->rateLimitEnabled === false) {
            return $handler->handle($request);
        }

        // 4. Check global feature flag when method has no explicit setting
        if ($method->rateLimitEnabled === null) {
            /** @var AbstractFeatures $features */
            $features = $this->container->get(AbstractFeatures::class);

            if (!$features->isEnabled('rate_limit')) {
                return $handler->handle($request);
            }
        }

        // 5. Resolve effective rate limit and key type
        /** @var RateLimitConfigInterface $config */
        $config           = $this->container->get(RateLimitConfigInterface::class);
        $effectiveLimit   = $method->rateLimit ?? RateLimit::fromConfig($config);
        $effectiveKeyType = $method->rateLimitKeyType ?? $effectiveLimit->keyType;

        // 6. Derive identity string and build composite key
        $identity     = $this->resolveIdentity($request, $effectiveKeyType);
        $routePattern = implode('/', [$domain, $version, $service, $resource]);
        $compositeKey = sprintf('rate:%s:%s:%s:%s', $effectiveKeyType->value, $identity, $httpMethod, $routePattern);

        // 7. Check rate limit; fail-open on any I/O error
        try {
            /** @var RateLimiterInterface $limiter */
            $limiter = $this->container->get(RateLimiterInterface::class);
            $result  = $limiter->check($compositeKey, $effectiveLimit);
        } catch (\Throwable) {
            return $handler->handle($request);
        }

        // 8. Reject with 429
        if (!$result->allowed) {
            return new Response(
                status:  429,
                headers: [
                    'Content-Type'          => 'application/json',
                    'Retry-After'           => (string) $result->resetIn,
                    'X-RateLimit-Limit'     => (string) $effectiveLimit->maxRequests,
                    'X-RateLimit-Remaining' => '0',
                ],
                body: json_encode(['error' => 'Too Many Requests']) ?: '{"error":"Too Many Requests"}',
            );
        }

        // 9. Forward and inject informational rate-limit headers
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $effectiveLimit->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining);
    }

    // ------------------------------------------------------------------
    // Identity resolution helpers
    // ------------------------------------------------------------------

    private function resolveIdentity(
        ServerRequestInterface $request,
        RateLimitKeyType $keyType,
    ): string {
        return match ($keyType) {
            RateLimitKeyType::Ip     => $this->resolveIp($request),
            RateLimitKeyType::UserId => $this->resolveUserId(),
            RateLimitKeyType::ApiKey => $this->resolveApiKey($request),
        };
    }

    private function resolveIp(ServerRequestInterface $request): string
    {
        $forwarded = $request->getHeaderLine('X-Forwarded-For');

        if ($forwarded !== '') {
            return trim(explode(',', $forwarded)[0]);
        }

        $serverParams = $request->getServerParams();

        return (string) ($serverParams['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private function resolveUserId(): string
    {
        try {
            /** @var WebUserInterface $user */
            $user = $this->container->get(WebUserInterface::class);
            $id   = $user->getId();

            return $id !== '' ? $id : 'guest';
        } catch (\Throwable) {
            return 'guest';
        }
    }

    private function resolveApiKey(ServerRequestInterface $request): string
    {
        $authorization = $request->getHeaderLine('Authorization');

        if (str_starts_with($authorization, 'Bearer ')) {
            return hash('sha256', substr($authorization, 7));
        }

        return $this->resolveIp($request);
    }
}
