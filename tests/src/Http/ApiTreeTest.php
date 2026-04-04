<?php

declare(strict_types=1);

use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Exception\ApiDefinitionException;
use Directive\Http\Exception\NotFoundException;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\VersionStatus;
use Directive\Service\Business\ErrorManager;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubRequestValidator;

function buildTestTree(): ApiDefinitionManager
{
    $manager = new ApiDefinitionManager();
    $domain  = new Domain('users');
    $domain->version('v1', VersionStatus::Open)
        ->service('account')
        ->resource('profile')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class);

    $manager->registerDomain($domain);
    return $manager;
}

describe('ApiDefinitionManager', function () {
    it('registers and retrieves a domain', function () {
        $manager = buildTestTree();
        $domain  = $manager->findDomain('users');
        expect($domain->name)->toBe('users');
    });

    it('throws NotFoundException for unknown domain', function () {
        $manager = buildTestTree();
        expect(fn() => $manager->findDomain('unknown'))->toThrow(NotFoundException::class);
    });

    it('throws ApiDefinitionException on duplicate domain', function () {
        $manager = buildTestTree();
        expect(fn() => $manager->registerDomain(new Domain('users')))->toThrow(ApiDefinitionException::class);
    });

    it('getDomains() returns all registered domains', function () {
        $manager = buildTestTree();
        expect($manager->getDomains())->toHaveKey('users');
    });
});

describe('Domain / Version / Service / Resource tree', function () {
    it('traverses the full tree', function () {
        $manager  = buildTestTree();
        $version  = $manager->findDomain('users')->findVersion('v1');
        $service  = $version->findService('account');
        $resource = $service->findResource('profile');
        $method   = $resource->findMethod('GET');

        expect($version->name)->toBe('v1');
        expect($service->name)->toBe('account');
        expect($resource->name)->toBe('profile');
        expect($method->httpMethod)->toBe('GET');
        expect($method->apiClass)->toBe(StubApi::class);
    });

    it('throws GoneException when version is Closed', function () {
        $domain = new Domain('d');
        $domain->version('v1', VersionStatus::Closed);
        expect(fn() => $domain->findVersion('v1')->checkAvailability())
            ->toThrow(\Directive\Http\Exception\GoneException::class);
    });

    it('throws GoneException when version is Wip', function () {
        $domain = new Domain('d');
        $domain->version('v1', VersionStatus::Wip);
        expect(fn() => $domain->findVersion('v1')->checkAvailability())
            ->toThrow(\Directive\Http\Exception\GoneException::class);
    });

    it('throws MethodNotAllowedException for unregistered HTTP verb', function () {
        $manager  = buildTestTree();
        $resource = $manager->findDomain('users')->findVersion('v1')->findService('account')->findResource('profile');
        expect(fn() => $resource->findMethod('DELETE'))
            ->toThrow(\Directive\Http\Exception\MethodNotAllowedException::class);
    });

    it('expose all HTTP methods via getMethods()', function () {
        $manager  = buildTestTree();
        $resource = $manager->findDomain('users')->findVersion('v1')->findService('account')->findResource('profile');
        expect($resource->getMethods())->toHaveKey('GET');
    });
});
