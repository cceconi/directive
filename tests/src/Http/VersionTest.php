<?php

declare(strict_types=1);

use Directive\Http\Exception\ApiDefinitionException;
use Directive\Http\Exception\NotFoundException;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\Resource;
use Directive\Http\Routing\Version;
use Directive\Http\Routing\VersionStatus;
use Tests\Helpers\StubApi;

describe('Version::resource() — direct resource registration', function () {
    it('creates and returns a Resource', function () {
        $domain  = new Domain('api');
        $version = $domain->version('v1', VersionStatus::Open);
        $result  = $version->resource('ping');

        expect($result)->toBeInstanceOf(Resource::class);
    });

    it('findResource() returns the registered resource', function () {
        $domain   = new Domain('api');
        $version  = $domain->version('v1', VersionStatus::Open);
        $resource = $version->resource('ping');

        expect($version->findResource('ping'))->toBe($resource);
    });

    it('findResource() throws NotFoundException for unknown name', function () {
        $domain  = new Domain('api');
        $version = $domain->version('v1', VersionStatus::Open);

        expect(fn() => $version->findResource('ghost'))->toThrow(NotFoundException::class);
    });

    it('getDirectResources() returns all registered direct resources', function () {
        $domain  = new Domain('api');
        $version = $domain->version('v1', VersionStatus::Open);
        $version->resource('ping');
        $version->resource('health');

        expect($version->getDirectResources())->toHaveKey('ping');
        expect($version->getDirectResources())->toHaveKey('health');
    });

    it('resource() propagates MethodDefaults from the version', function () {
        $domain  = new Domain('api');
        $version = $domain->version('v1', VersionStatus::Open)
            ->withErrorClass(\Directive\Service\Business\ErrorManager::class);
        $version->resource('ping')->get(StubApi::class);

        $method = $version->findResource('ping')->findMethod('GET');

        expect($method->errorClass)->toBe(\Directive\Service\Business\ErrorManager::class);
    });
});

describe('Version — anti-collision between Service and direct Resource', function () {
    it('resource() after service() on same name throws ApiDefinitionException', function () {
        $domain  = new Domain('api');
        $version = $domain->version('v1', VersionStatus::Open);
        $version->service('users');

        expect(fn() => $version->resource('users'))->toThrow(ApiDefinitionException::class);
    });

    it('service() after resource() on same name throws ApiDefinitionException', function () {
        $domain  = new Domain('api');
        $version = $domain->version('v1', VersionStatus::Open);
        $version->resource('health');

        expect(fn() => $version->service('health'))->toThrow(ApiDefinitionException::class);
    });

    it('same name can be used across different Versions without conflict', function () {
        $domain = new Domain('api');
        $domain->version('v1', VersionStatus::Open)->resource('ping');
        $domain->version('v2', VersionStatus::Open)->service('ping');

        // No exception — registries are per-Version
        expect(true)->toBeTrue();
    });

    it('duplicate direct resource name throws ApiDefinitionException', function () {
        $domain  = new Domain('api');
        $version = $domain->version('v1', VersionStatus::Open);
        $version->resource('ping');

        expect(fn() => $version->resource('ping'))->toThrow(ApiDefinitionException::class);
    });
});
