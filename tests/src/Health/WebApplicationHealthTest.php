<?php

declare(strict_types=1);

use Directive\Http\Response\HttpResponse;
use Directive\Service\AppManagement\AppInfoInterface;
use Directive\Service\AppManagement\ClientHeadersInterface;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Health\AbstractHealthCheck;
use Directive\Service\Health\HealthCheckInterface;
use Directive\Service\Logging\WebLoggerInterface;
use Directive\Service\Maintenance\MaintenanceManagerInterface;
use Directive\Service\Security\AccessManagerInterface;
use Directive\Service\Security\CookiesManagerInterface;
use Directive\Service\Security\HeaderManagerInterface;
use Directive\Service\Security\WebUserInterface;
use Directive\WebApplication;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Tests\Helpers\TestConfig;

// ---------------------------------------------------------------------------
// Stubs (prefixed to avoid redeclaration conflicts)
// ---------------------------------------------------------------------------

final class HRStubWebLogger implements WebLoggerInterface
{
    public function logVersion(string $version): void {}
    public function logBusinessTimeExecution(float $elapsed): void {}
    /** @param array<int, array<string, mixed>> $points */
    public function logBusinessBenchmark(array $points): void {}
    public function logRaw(string $message, mixed $context = null): void {}
    public function logRequest(string $url, ServerRequestInterface $request): void {}
    public function logResponse(ResponseInterface $response, string $message, mixed $data = null): void {}
    public function logWebUser(?WebUserInterface $webUser): void {}
    public function logJwt(string $jwt): void {}
    public function logApiVersion(string $name, string $status, string $info = ''): void {}
    public function write(): void {}
    public function logError(\Throwable $e): void {}
}

final class HRStubAppInfo implements AppInfoInterface
{
    public function getVersion(): string { return '0.0.0-test'; }
    public function getName(): string    { return 'test'; }
}

final class HRStubHeaderManager implements HeaderManagerInterface
{
    public function addCors(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
    public function validateSecurityHeader(ServerRequestInterface $request): bool { return true; }
    public function updateSecurityHeader(ResponseInterface $response): ResponseInterface { return $response; }
    public function getError(): string { return ''; }
}

final class HRStubMaintenanceOff implements MaintenanceManagerInterface
{
    public function isActive(): bool     { return false; }
    public function getMessage(): string { return ''; }
    public function getPeriod(): string  { return ''; }
}

final class HRStubMaintenanceOn implements MaintenanceManagerInterface
{
    public function isActive(): bool     { return true; }
    public function getMessage(): string { return 'Down for maintenance'; }
    public function getPeriod(): string  { return ''; }
}

final class HRStubClientHeaders implements ClientHeadersInterface
{
    public function load(ServerRequestInterface $request): void {}
    public function get(string $headerName): ?string { return null; }
}

final class HRStubCookiesManager implements CookiesManagerInterface
{
    public function storeFromRequest(ServerRequestInterface $request): void {}
    public function setResponseCookies(ResponseInterface $response): ResponseInterface { return $response; }
    public function addResponseCookie(string $name, string $value, ?int $maxAge = null, ?string $domain = null): void {}
    public function addResponseAccessTokenCookie(string $value, ?int $maxAge = null, ?string $domain = null): void {}
}

final class HRStubAccessManager implements AccessManagerInterface
{
    public function authenticate(ServerRequestInterface $request): void {}
}

final class HRPassCheck extends AbstractHealthCheck
{
    protected function run(): array { return ['status' => 'pass']; }
}

final class HRFailCheck extends AbstractHealthCheck
{
    protected function run(): array { return ['status' => 'fail']; }
}

// ---------------------------------------------------------------------------
// Base application for health route tests
// ---------------------------------------------------------------------------

class HealthRouteWebApplication extends WebApplication
{
    /** @var array<string, HealthCheckInterface> */
    public array $injectedChecks = [];

    /** @var MaintenanceManagerInterface|null */
    public ?MaintenanceManagerInterface $maintenanceOverride = null;

    protected function registerServices(AbstractConfiguration $config): void
    {
        parent::registerServices($config);

        $factory     = new Psr17Factory();
        $maintenance = $this->maintenanceOverride ?? new HRStubMaintenanceOff();

        $this->addDefinitions([
            WebLoggerInterface::class                       => new HRStubWebLogger(),
            AppInfoInterface::class                         => new HRStubAppInfo(),
            HeaderManagerInterface::class                   => new HRStubHeaderManager(),
            HttpResponse::class                             => new HttpResponse($factory, $factory),
            MaintenanceManagerInterface::class              => $maintenance,
            ClientHeadersInterface::class                   => new HRStubClientHeaders(),
            \Psr\Http\Message\StreamFactoryInterface::class => $factory,
            CookiesManagerInterface::class                  => new HRStubCookiesManager(),
            AccessManagerInterface::class                   => new HRStubAccessManager(),
        ]);
    }

    /** @param App<\Psr\Container\ContainerInterface|null> $slim */
    protected function configureMiddleware(App $slim): void {}

    /** @return array<string, HealthCheckInterface> */
    protected function configureHealthChecks(): array
    {
        return $this->injectedChecks;
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeHealthApp(?MaintenanceManagerInterface $maintenance = null): HealthRouteWebApplication
{
    $app = new HealthRouteWebApplication();
    $app->maintenanceOverride = $maintenance;
    $app->setConfig(TestConfig::class);
    return $app;
}

function decodeHealthRoute(\Psr\Http\Message\ResponseInterface $response): array
{
    return json_decode((string) $response->getBody(), true);
}

// ---------------------------------------------------------------------------
// GET /health/live
// ---------------------------------------------------------------------------

describe('GET /health/live', function (): void {

    afterEach(function (): void {
        unset($_ENV['HEALTH_DISABLED']);
    });

    it('returns HTTP 200', function (): void {
        $app      = makeHealthApp();
        $request  = new ServerRequest('GET', '/health/live');
        $response = $app->resolve($request);

        expect($response->getStatusCode())->toBe(200);
    });

    it('responds with application/health+json content type', function (): void {
        $app      = makeHealthApp();
        $request  = new ServerRequest('GET', '/health/live');
        $response = $app->resolve($request);

        expect($response->getHeaderLine('Content-Type'))->toBe('application/health+json');
    });

    it('body contains status "pass"', function (): void {
        $app      = makeHealthApp();
        $request  = new ServerRequest('GET', '/health/live');
        $body     = decodeHealthRoute($app->resolve($request));

        expect($body['status'])->toBe('pass');
    });

    it('returns 404 when HEALTH_DISABLED=true', function (): void {
        $_ENV['HEALTH_DISABLED'] = 'true';
        $app      = makeHealthApp();
        $request  = new ServerRequest('GET', '/health/live');
        $response = $app->resolve($request);

        expect($response->getStatusCode())->toBe(404);
    });
});

// ---------------------------------------------------------------------------
// GET /health/ready
// ---------------------------------------------------------------------------

describe('GET /health/ready', function (): void {

    afterEach(function (): void {
        unset($_ENV['HEALTH_DISABLED']);
    });

    it('returns HTTP 200 with no application checks', function (): void {
        $app      = makeHealthApp();
        $request  = new ServerRequest('GET', '/health/ready');
        $response = $app->resolve($request);
        $body     = decodeHealthRoute($response);

        expect($response->getStatusCode())->toBe(200);
        expect($body['status'])->toBe('pass');
        expect($body['checks'])->toBe([]);
    });

    it('returns HTTP 200 when configureHealthChecks() returns passing check', function (): void {
        $request  = new ServerRequest('GET', '/health/ready');

        // Must boot with injectedChecks set before setConfig() so the closure captures them
        $app2 = new HealthRouteWebApplication();
        $app2->injectedChecks = ['self' => new HRPassCheck()];
        $app2->setConfig(TestConfig::class);

        $response = $app2->resolve($request);
        $body     = decodeHealthRoute($response);

        expect($response->getStatusCode())->toBe(200);
        expect($body['checks'])->toHaveKey('self');
        expect($body['checks']['self'][0]['status'])->toBe('pass');
    });

    it('returns HTTP 503 when maintenance is active', function (): void {
        $app      = makeHealthApp(new HRStubMaintenanceOn());
        $request  = new ServerRequest('GET', '/health/ready');
        $response = $app->resolve($request);

        expect($response->getStatusCode())->toBe(503);
    });

    it('returns 404 when HEALTH_DISABLED=true', function (): void {
        $_ENV['HEALTH_DISABLED'] = 'true';
        $app      = makeHealthApp();
        $request  = new ServerRequest('GET', '/health/ready');
        $response = $app->resolve($request);

        expect($response->getStatusCode())->toBe(404);
    });
});
