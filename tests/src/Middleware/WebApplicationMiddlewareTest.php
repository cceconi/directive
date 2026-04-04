<?php

declare(strict_types=1);

use Directive\Http\Response\HttpResponse;
use Directive\Service\AppManagement\AppInfoInterface;
use Directive\Service\AppManagement\ClientHeadersInterface;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\DirectiveLogger;
use Directive\Service\Logging\RequestIdHolder;
use Directive\Service\Maintenance\MaintenanceManagerInterface;
use Directive\Service\Security\AccessManagerInterface;
use Directive\Service\Security\CookiesManagerInterface;
use Directive\Service\Security\HeaderManagerInterface;
use Directive\WebApplication;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Slim\App;
use Tests\Helpers\TestConfig;

// ---------------------------------------------------------------------------
// No-op stubs for all services required by the 8 default middlewares.
// ---------------------------------------------------------------------------

final class StubAppInfo implements AppInfoInterface
{
    public function getVersion(): string
    {
        return '0.0.0-test';
    }
    public function getName(): string
    {
        return 'test';
    }
}

final class StubHeaderManager implements HeaderManagerInterface
{
    public function addCors(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    public function validateSecurityHeader(ServerRequestInterface $request): bool
    {
        return true;
    }
    public function updateSecurityHeader(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    public function getError(): string
    {
        return '';
    }
}

final class StubMaintenance implements MaintenanceManagerInterface
{
    public function isActive(): bool
    {
        return false;
    }
    public function getMessage(): string
    {
        return '';
    }
    public function getPeriod(): string
    {
        return '';
    }
}

final class StubClientHeaders implements ClientHeadersInterface
{
    public function load(ServerRequestInterface $request): void {}
    public function get(string $headerName): ?string
    {
        return null;
    }
}

final class StubCookiesManager implements CookiesManagerInterface
{
    public function storeFromRequest(ServerRequestInterface $request): void {}
    public function setResponseCookies(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    public function addResponseCookie(string $name, string $value, ?int $maxAge = null, ?string $domain = null): void {}
    public function addResponseAccessTokenCookie(string $value, ?int $maxAge = null, ?string $domain = null): void {}
}

final class StubAccessManager implements AccessManagerInterface
{
    public function authenticate(ServerRequestInterface $request): void {}
}

// ---------------------------------------------------------------------------
// Full-stack test double: stubs all unimplemented middleware services.
// ---------------------------------------------------------------------------

final class FullStackWebApplication extends WebApplication
{
    public bool $configureMiddlewareCalled = false;

    protected function registerServices(AbstractConfiguration $config): void
    {
        parent::registerServices($config);

        $factory = new Psr17Factory();
        $this->addDefinitions([
            \Psr\Log\LoggerInterface::class                 => new NullLogger(),
            AppInfoInterface::class                         => new StubAppInfo(),
            HeaderManagerInterface::class                   => new StubHeaderManager(),
            HttpResponse::class                             => new HttpResponse($factory, $factory),
            MaintenanceManagerInterface::class              => new StubMaintenance(),
            ClientHeadersInterface::class                   => new StubClientHeaders(),
            \Psr\Http\Message\StreamFactoryInterface::class => $factory,
            CookiesManagerInterface::class                  => new StubCookiesManager(),
            AccessManagerInterface::class                   => new StubAccessManager(),
        ]);
    }

    /** @param App<\Psr\Container\ContainerInterface|null> $slim */
    protected function configureMiddleware(App $slim): void
    {
        $this->configureMiddlewareCalled = true;
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('WebApplication middleware integration', function (): void {

    it('calls configureMiddleware hook during boot', function (): void {
        $app = new FullStackWebApplication();
        $app->setConfig(TestConfig::class);

        expect($app->configureMiddlewareCalled)->toBeTrue();
    });

    it('sets X-Request-Id response header proving RequestIdMiddleware is wired', function (): void {
        $app = new FullStackWebApplication();
        $app->setConfig(TestConfig::class);

        $request  = new ServerRequest('GET', '/any/v1/foo/bar');
        $response = $app->resolve($request);

        expect($response->getHeaderLine('X-Request-Id'))->not->toBeEmpty();
    });

    it('generates a UUID v7 when no X-Request-Id is provided', function (): void {
        $app = new FullStackWebApplication();
        $app->setConfig(TestConfig::class);

        $request  = new ServerRequest('GET', '/any/v1/foo/bar');
        $response = $app->resolve($request);

        $requestId = $response->getHeaderLine('X-Request-Id');
        expect($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    it('propagates an existing X-Request-Id through the full stack', function (): void {
        $app = new FullStackWebApplication();
        $app->setConfig(TestConfig::class);

        $existingId = 'upstream-gateway-id-xyz';
        $request    = new ServerRequest('GET', '/any/v1/foo/bar')
            ->withHeader('X-Request-Id', $existingId);

        $response = $app->resolve($request);

        expect($response->getHeaderLine('X-Request-Id'))->toBe($existingId);
    });
});

// ---------------------------------------------------------------------------
// Test double for HttpDebugLoggingMiddleware integration
// ---------------------------------------------------------------------------

final class DebugLoggingWebApplication extends WebApplication
{
    public TestHandler $testHandler;

    protected function registerServices(AbstractConfiguration $config): void
    {
        parent::registerServices($config);

        $this->testHandler = new TestHandler(Level::Debug, bubble: false);

        $loggingConfig = new DefaultLoggingConfig();
        $loggingConfig->audit();
        $holder = new RequestIdHolder();
        $logger = new DirectiveLogger($loggingConfig, $holder);
        $logger->setHandlers([$this->testHandler]);

        $factory = new Psr17Factory();
        $this->addDefinitions([
            DirectiveLogger::class                          => $logger,
            LoggerInterface::class                          => $logger,
            RequestIdHolder::class                          => $holder,
            AppInfoInterface::class                         => new StubAppInfo(),
            HeaderManagerInterface::class                   => new StubHeaderManager(),
            HttpResponse::class                             => new HttpResponse($factory, $factory),
            MaintenanceManagerInterface::class              => new StubMaintenance(),
            ClientHeadersInterface::class                   => new StubClientHeaders(),
            \Psr\Http\Message\StreamFactoryInterface::class => $factory,
            CookiesManagerInterface::class                  => new StubCookiesManager(),
            AccessManagerInterface::class                   => new StubAccessManager(),
        ]);
    }
}

// ---------------------------------------------------------------------------
// Tests — S-01 HttpDebugLoggingMiddleware wiring via DirectiveFeatures flag
// ---------------------------------------------------------------------------

describe('WebApplication HttpDebugLoggingMiddleware wiring', function (): void {

    afterEach(function (): void {
        unset($_ENV['DIRECTIVE_DEBUG_LOGGING']);
    });

    it('wires HttpDebugLoggingMiddleware and logs request/response when debug_logging is enabled', function (): void {
        $_ENV['DIRECTIVE_DEBUG_LOGGING'] = '1';

        $app = new DebugLoggingWebApplication();
        $app->setConfig(TestConfig::class);

        $request = new ServerRequest('GET', '/any/v1/foo/bar');
        $app->resolve($request);

        $messages = array_column($app->testHandler->getRecords(), 'message');
        expect($messages)->toContain('http.request');
        expect($messages)->toContain('http.response');
    });

    it('does not wire HttpDebugLoggingMiddleware when debug_logging is disabled', function (): void {
        unset($_ENV['DIRECTIVE_DEBUG_LOGGING']);

        $app = new DebugLoggingWebApplication();
        $app->setConfig(TestConfig::class);

        $request = new ServerRequest('GET', '/any/v1/foo/bar');
        $app->resolve($request);

        $messages = array_column($app->testHandler->getRecords(), 'message');
        expect($messages)->not->toContain('http.request');
        expect($messages)->not->toContain('http.response');
    });
});
