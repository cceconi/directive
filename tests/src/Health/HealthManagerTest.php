<?php

declare(strict_types=1);

use Directive\Service\AppManagement\AppInfoInterface;
use Directive\Service\Health\AbstractHealthCheck;
use Directive\Service\Health\HealthManager;
use Directive\Service\Maintenance\MaintenanceManagerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

final class HMStubAppInfo implements AppInfoInterface
{
    public function getVersion(): string
    {
        return '1.2.3';
    }
    public function getName(): string
    {
        return 'test-app';
    }
}

final class HMStubMaintenanceOff implements MaintenanceManagerInterface
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

final class HMStubMaintenanceOn implements MaintenanceManagerInterface
{
    public function isActive(): bool
    {
        return true;
    }
    public function getMessage(): string
    {
        return 'Scheduled maintenance';
    }
    public function getPeriod(): string
    {
        return '';
    }
}

final class PassCheck extends AbstractHealthCheck
{
    protected function run(): array
    {
        return ['status' => 'pass'];
    }
}

final class WarnCheck extends AbstractHealthCheck
{
    protected function run(): array
    {
        return ['status' => 'warn'];
    }
}

final class FailCheck extends AbstractHealthCheck
{
    protected function run(): array
    {
        return ['status' => 'fail'];
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeManager(
    ?MaintenanceManagerInterface $maintenance = null,
): HealthManager {
    return new HealthManager(
        new HMStubAppInfo(),
        $maintenance ?? new HMStubMaintenanceOff(),
        new Psr17Factory(),
    );
}

function emptyResponse(): \Psr\Http\Message\ResponseInterface
{
    return new Response();
}

function decodeHealthBody(\Psr\Http\Message\ResponseInterface $response): array
{
    return json_decode((string) $response->getBody(), true);
}

// ---------------------------------------------------------------------------
// resolveLive()
// ---------------------------------------------------------------------------

describe('resolveLive()', function (): void {

    afterEach(function (): void {
        unset($_ENV['HEALTH_DISABLED']);
    });

    it('returns HTTP 200', function (): void {
        $response = makeManager()->resolveLive(emptyResponse());
        expect($response->getStatusCode())->toBe(200);
    });

    it('sets Content-Type to application/health+json', function (): void {
        $response = makeManager()->resolveLive(emptyResponse());
        expect($response->getHeaderLine('Content-Type'))->toBe('application/health+json');
    });

    it('body has status "pass"', function (): void {
        $body = decodeHealthBody(makeManager()->resolveLive(emptyResponse()));
        expect($body['status'])->toBe('pass');
    });

    it('body contains version from AppInfoInterface', function (): void {
        $body = decodeHealthBody(makeManager()->resolveLive(emptyResponse()));
        expect($body['version'])->toBe('1.2.3');
    });

    it('returns HTTP 404 when HEALTH_DISABLED=true', function (): void {
        $_ENV['HEALTH_DISABLED'] = 'true';
        $response = makeManager()->resolveLive(emptyResponse());
        expect($response->getStatusCode())->toBe(404);
    });
});

// ---------------------------------------------------------------------------
// resolveReady()
// ---------------------------------------------------------------------------

describe('resolveReady()', function (): void {

    afterEach(function (): void {
        unset($_ENV['HEALTH_DISABLED']);
    });

    it('returns HTTP 200 with no checks and no maintenance', function (): void {
        $response = makeManager()->resolveReady(emptyResponse(), []);
        expect($response->getStatusCode())->toBe(200);
    });

    it('returns HTTP 200 when all checks pass', function (): void {
        $response = makeManager()->resolveReady(emptyResponse(), [
            'db' => new PassCheck(),
        ]);
        expect($response->getStatusCode())->toBe(200);
    });

    it('returns HTTP 200 when a check returns warn', function (): void {
        $response = makeManager()->resolveReady(emptyResponse(), [
            'cache' => new WarnCheck(),
        ]);
        expect($response->getStatusCode())->toBe(200);
        $body = decodeHealthBody($response);
        expect($body['status'])->toBe('warn');
    });

    it('returns HTTP 503 when a check returns fail', function (): void {
        $response = makeManager()->resolveReady(emptyResponse(), [
            'db' => new FailCheck(),
        ]);
        expect($response->getStatusCode())->toBe(503);
        $body = decodeHealthBody($response);
        expect($body['status'])->toBe('fail');
    });

    it('returns HTTP 503 when maintenance is active', function (): void {
        $manager  = makeManager(new HMStubMaintenanceOn());
        $response = $manager->resolveReady(emptyResponse(), []);
        expect($response->getStatusCode())->toBe(503);
    });

    it('includes maintenance entry in checks when maintenance is active', function (): void {
        $manager = makeManager(new HMStubMaintenanceOn());
        $body    = decodeHealthBody($manager->resolveReady(emptyResponse(), []));
        expect($body['checks'])->toHaveKey('maintenance');
        expect($body['checks']['maintenance'][0]['status'])->toBe('fail');
    });

    it('includes named check results in checks body', function (): void {
        $response = makeManager()->resolveReady(emptyResponse(), [
            'external-api' => new PassCheck(),
        ]);
        $body = decodeHealthBody($response);
        expect($body['checks'])->toHaveKey('external-api');
    });

    it('sets Content-Type to application/health+json', function (): void {
        $response = makeManager()->resolveReady(emptyResponse(), []);
        expect($response->getHeaderLine('Content-Type'))->toBe('application/health+json');
    });

    it('returns HTTP 404 when HEALTH_DISABLED=true', function (): void {
        $_ENV['HEALTH_DISABLED'] = 'true';
        $response = makeManager()->resolveReady(emptyResponse(), []);
        expect($response->getStatusCode())->toBe(404);
    });
});
