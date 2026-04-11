<?php

declare(strict_types=1);

use Directive\Service\AppManagement\AppInfo;
use Directive\Service\AppManagement\AppInfoService;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

describe('AppInfoService', function (): void {

    function makeAppInfoService(array $data = [], string $managementToken = ''): AppInfoService
    {
        $config = makeTestConfig(['MANAGEMENT_TOKEN' => $managementToken]);
        return new AppInfoService(new AppInfo($data), new Psr17Factory(), $config);
    }

    function makeDetailsRequest(string $authHeader = ''): ServerRequest
    {
        $req = new ServerRequest('GET', '/info/details');
        return $authHeader !== '' ? $req->withHeader('Authorization', $authHeader) : $req;
    }

    // ------------------------------------------------------------------
    // publicInfo()
    // ------------------------------------------------------------------

    it('publicInfo returns name and version only', function (): void {
        $service = makeAppInfoService([
            'name'        => 'my-app',
            'version'     => '1.2.3',
            'commitId'    => 'abc1234',
            'branch'      => 'main',
        ]);

        expect($service->publicInfo())->toBe([
            'name'    => 'my-app',
            'version' => '1.2.3',
        ]);
    });

    it('publicInfo returns empty strings when AppInfo is empty', function (): void {
        $service = makeAppInfoService();

        expect($service->publicInfo())->toBe(['name' => '', 'version' => '']);
    });

    // ------------------------------------------------------------------
    // fullInfo()
    // ------------------------------------------------------------------

    it('fullInfo returns all 8 fields', function (): void {
        $service = makeAppInfoService([
            'name'        => 'my-app',
            'version'     => '1.2.3',
            'commitId'    => 'abc1234',
            'branch'      => 'main',
            'tag'         => 'v1.2.3',
            'buildNumber' => '42',
            'builtAt'     => '2026-04-11T10:00:00Z',
            'builtBy'     => 'ci-bot',
        ]);

        expect($service->fullInfo())->toBe([
            'name'        => 'my-app',
            'version'     => '1.2.3',
            'commitId'    => 'abc1234',
            'branch'      => 'main',
            'tag'         => 'v1.2.3',
            'buildNumber' => '42',
            'builtAt'     => '2026-04-11T10:00:00Z',
            'builtBy'     => 'ci-bot',
        ]);
    });

    it('fullInfo returns empty strings for missing build info', function (): void {
        $service = makeAppInfoService(['name' => 'app', 'version' => '0.1.0']);

        $info = $service->fullInfo();

        expect($info['name'])->toBe('app');
        expect($info['version'])->toBe('0.1.0');
        expect($info['commitId'])->toBe('');
        expect($info['branch'])->toBe('');
        expect($info['tag'])->toBe('');
        expect($info['buildNumber'])->toBe('');
        expect($info['builtAt'])->toBe('');
        expect($info['builtBy'])->toBe('');
    });

    // ------------------------------------------------------------------
    // resolvePublic()
    // ------------------------------------------------------------------

    it('resolvePublic returns 200 with application/json', function (): void {
        $service  = makeAppInfoService(['name' => 'my-app', 'version' => '2.0.0']);
        $response = $service->resolvePublic(new Response());

        expect($response->getStatusCode())->toBe(200);
        expect($response->getHeaderLine('Content-Type'))->toBe('application/json');
    });

    it('resolvePublic body contains only name and version', function (): void {
        $service  = makeAppInfoService(['name' => 'my-app', 'version' => '2.0.0', 'commitId' => 'xyz']);
        $response = $service->resolvePublic(new Response());
        $body     = json_decode((string) $response->getBody(), true);

        expect($body)->toBe(['name' => 'my-app', 'version' => '2.0.0']);
    });

    // ------------------------------------------------------------------
    // resolveDetails() — management token auth
    // ------------------------------------------------------------------

    it('resolveDetails returns 503 when MANAGEMENT_TOKEN is not configured', function (): void {
        $service  = makeAppInfoService(['name' => 'my-app'], managementToken: '');
        $response = $service->resolveDetails(makeDetailsRequest(), new Response());

        expect($response->getStatusCode())->toBe(503);
    });

    it('resolveDetails returns 401 with WWW-Authenticate when Authorization header is missing', function (): void {
        $service  = makeAppInfoService(['name' => 'my-app'], managementToken: 'secret-token');
        $response = $service->resolveDetails(makeDetailsRequest(), new Response());

        expect($response->getStatusCode())->toBe(401);
        expect($response->getHeaderLine('WWW-Authenticate'))->toBe('Bearer realm="management"');
        expect($response->getHeaderLine('Content-Type'))->toBe('application/json');
    });

    it('resolveDetails returns 403 when token does not match', function (): void {
        $service  = makeAppInfoService(['name' => 'my-app'], managementToken: 'secret-token');
        $response = $service->resolveDetails(makeDetailsRequest('Bearer wrong-token'), new Response());

        expect($response->getStatusCode())->toBe(403);
    });

    it('resolveDetails returns 200 with full info when token is correct', function (): void {
        $service  = makeAppInfoService(['name' => 'my-app', 'version' => '2.0.0'], managementToken: 'secret-token');
        $response = $service->resolveDetails(makeDetailsRequest('Bearer secret-token'), new Response());

        expect($response->getStatusCode())->toBe(200);
        expect($response->getHeaderLine('Content-Type'))->toBe('application/json');
    });

    it('resolveDetails body contains all 8 fields when authenticated', function (): void {
        $service  = makeAppInfoService([
            'name'        => 'my-app',
            'version'     => '2.0.0',
            'commitId'    => 'abc',
            'branch'      => 'main',
            'tag'         => '',
            'buildNumber' => '7',
            'builtAt'     => '2026-04-11T10:00:00Z',
            'builtBy'     => 'ci',
        ], managementToken: 'secret-token');
        $response = $service->resolveDetails(makeDetailsRequest('Bearer secret-token'), new Response());
        $body     = json_decode((string) $response->getBody(), true);

        expect($body)->toHaveKeys(['name', 'version', 'commitId', 'branch', 'tag', 'buildNumber', 'builtAt', 'builtBy']);
        expect($body['commitId'])->toBe('abc');
        expect($body['builtBy'])->toBe('ci');
    });
});
