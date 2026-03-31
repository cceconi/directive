<?php

declare(strict_types=1);

use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\VersionStatus;
use Directive\Service\Business\ErrorManager;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubRequestValidator;
use Tests\Helpers\StubWebUser;

function buildRouterTree(): ApiDefinitionManager
{
    $manager = new ApiDefinitionManager();
    $domain  = new Domain('api');
    $domain->version('v1', VersionStatus::Open)
        ->service('test')
        ->resource('item')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class)->withAllowedRoles(['admin'])
        ->post(StubApi::class);

    $closedDomain = new Domain('old');
    $closedDomain->version('v1', VersionStatus::Closed)
        ->service('test')
        ->resource('item')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class);

    $manager->registerDomain($domain);
    $manager->registerDomain($closedDomain);

    return $manager;
}

describe('Router dispatch', function () {
    it('returns 200 for a valid GET request', function () {
        $manager = buildRouterTree();
        $extra   = [StubRequestValidator::class => new StubRequestValidator()];
        $router  = $this->buildRouter($manager, null, $extra);
        $resp    = $this->dispatch($router, 'GET', 'api', 'v1', 'test', 'item');

        $this->assertResponseStatus($resp, 200);
    });

    it('returns 200 with hello/world payload', function () {
        $manager = buildRouterTree();
        $extra   = [StubRequestValidator::class => new StubRequestValidator()];
        $router  = $this->buildRouter($manager, null, $extra);
        $resp    = $this->dispatch($router, 'GET', 'api', 'v1', 'test', 'item');

        $this->assertJsonDataContains($resp, 'hello', 'world');
    });

    it('returns 404 for an unknown domain', function () {
        $manager = buildRouterTree();
        $router  = $this->buildRouter($manager);
        $resp    = $this->dispatch($router, 'GET', 'nope', 'v1', 'test', 'item');

        $this->assertResponseStatus($resp, 404);
    });

    it('returns 404 for an unknown resource', function () {
        $manager = buildRouterTree();
        $router  = $this->buildRouter($manager);
        $resp    = $this->dispatch($router, 'GET', 'api', 'v1', 'test', 'ghost');

        $this->assertResponseStatus($resp, 404);
    });

    it('returns 405 for a method not registered on a resource', function () {
        $manager = buildRouterTree();
        $router  = $this->buildRouter($manager);
        $resp    = $this->dispatch($router, 'DELETE', 'api', 'v1', 'test', 'item');

        $this->assertResponseStatus($resp, 405);
    });

    it('returns 410 for a closed version', function () {
        $manager = buildRouterTree();
        $router  = $this->buildRouter($manager);
        $resp    = $this->dispatch($router, 'GET', 'old', 'v1', 'test', 'item');

        $this->assertResponseStatus($resp, 410);
    });

    it('returns 401 when profiles required but user is guest', function () {
        $manager = buildRouterTree();
        $extra   = [StubRequestValidator::class => new StubRequestValidator()];
        $router  = $this->buildRouter($manager, new StubWebUser(), $extra);
        $resp    = $this->dispatch($router, 'POST', 'api', 'v1', 'test', 'item');

        $this->assertResponseStatus($resp, 401);
    });

    it('returns 403 when user profile not in allowed list', function () {
        $manager = buildRouterTree();
        $extra   = [StubRequestValidator::class => new StubRequestValidator()];
        $user    = new StubWebUser()->withProfile('user');
        $router  = $this->buildRouter($manager, $user, $extra);
        $resp    = $this->dispatch($router, 'POST', 'api', 'v1', 'test', 'item');

        $this->assertResponseStatus($resp, 403);
    });

    it('returns 200 when user profile matches allowed list', function () {
        $manager = buildRouterTree();
        $extra   = [StubRequestValidator::class => new StubRequestValidator()];
        $user    = new StubWebUser()->withProfile('admin');
        $router  = $this->buildRouter($manager, $user, $extra);
        $resp    = $this->dispatch($router, 'POST', 'api', 'v1', 'test', 'item');

        $this->assertResponseStatus($resp, 200);
    });

    it('dispatches without error when no requestValidatorClass is configured (NullRequestValidator default)', function () {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('open');
        $domain->version('v1', VersionStatus::Open)
            ->service('pub')
            ->resource('health')
            ->get(StubApi::class); // no withRequestValidatorClass() — NullRequestValidator resolved by container

        $manager->registerDomain($domain);
        $router = $this->buildRouter($manager); // no $extra — PHP-DI auto-wires NullRequestValidator
        $resp   = $this->dispatch($router, 'GET', 'open', 'v1', 'pub', 'health');

        $this->assertResponseStatus($resp, 200);
    });
});

function buildRouterTreeWithDirectResource(): ApiDefinitionManager
{
    $manager = new ApiDefinitionManager();
    $domain  = new Domain('api');
    $version = $domain->version('v1', VersionStatus::Open);

    // 4-level (service-level) resource — unchanged
    $version->service('users')
        ->resource('profile')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class);

    // 3-level (direct) resource — new
    $version->resource('ping')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class)
        ->post(StubApi::class);

    $manager->registerDomain($domain);

    return $manager;
}

describe('Router dispatch — 3-segment (direct resource)', function () {
    it('returns 200 for a valid GET request on a direct resource', function () {
        $manager = buildRouterTreeWithDirectResource();
        $extra   = [StubRequestValidator::class => new StubRequestValidator()];
        $router  = $this->buildRouter($manager, null, $extra);
        $resp    = $this->dispatch($router, 'GET', 'api', 'v1', '', 'ping');

        $this->assertResponseStatus($resp, 200);
    });

    it('returns 404 for an unknown direct resource', function () {
        $manager = buildRouterTreeWithDirectResource();
        $router  = $this->buildRouter($manager);
        $resp    = $this->dispatch($router, 'GET', 'api', 'v1', '', 'ghost');

        $this->assertResponseStatus($resp, 404);
    });

    it('returns 405 for an unregistered method on a direct resource', function () {
        $manager = buildRouterTreeWithDirectResource();
        $router  = $this->buildRouter($manager);
        $resp    = $this->dispatch($router, 'DELETE', 'api', 'v1', '', 'ping');

        $this->assertResponseStatus($resp, 405);
    });

    it('4-level routes still resolve correctly when a direct resource also exists', function () {
        $manager = buildRouterTreeWithDirectResource();
        $extra   = [StubRequestValidator::class => new StubRequestValidator()];
        $router  = $this->buildRouter($manager, null, $extra);
        $resp    = $this->dispatch($router, 'GET', 'api', 'v1', 'users', 'profile');

        $this->assertResponseStatus($resp, 200);
    });
});

describe('Router OPTIONS — 3-segment (direct resource)', function () {
    it('returns 204 with Allow header for a direct resource', function () {
        $manager = buildRouterTreeWithDirectResource();
        $router  = $this->buildRouter($manager);

        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $uri     = '/api/v1/ping';
        $request = $factory->createServerRequest('OPTIONS', $uri);
        $args    = ['domain' => 'api', 'version' => 'v1', 'resource' => 'ping'];
        $resp    = $router->resolveOptions($request, $factory->createResponse(), $args);

        $this->assertResponseStatus($resp, 204);
        expect($resp->getHeaderLine('Allow'))->toContain('GET');
        expect($resp->getHeaderLine('Allow'))->toContain('POST');
        expect($resp->getHeaderLine('Allow'))->toContain('OPTIONS');
    });
});
