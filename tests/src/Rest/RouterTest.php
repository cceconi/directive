<?php

declare(strict_types=1);

use Directive\Rest\ApiDefinitionManager;
use Directive\Rest\Domain;
use Directive\Rest\VersionStatus;
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
        ->get(StubApi::class, StubRequestValidator::class, ErrorManager::class)
        ->post(StubApi::class, StubRequestValidator::class, ErrorManager::class, allowedRoles: ['admin']);

    $closedDomain = new Domain('old');
    $closedDomain->version('v1', VersionStatus::Closed)
        ->service('test')
        ->resource('item')
        ->get(StubApi::class, StubRequestValidator::class, ErrorManager::class);

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
});
