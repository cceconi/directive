<?php

declare(strict_types=1);

use Directive\Http\Routing\Domain;
use Directive\Http\Routing\MethodDefaults;
use Directive\Http\Validator\NullRequestValidator;
use Directive\Http\Routing\VersionStatus;
use Directive\Service\Business\ErrorManager;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubErrorManager;
use Tests\Helpers\StubRequestValidator;

describe('MethodDefaults cascade — Domain level', function () {
    it('propagates errorClass set on Domain down to Method', function () {
        $domain = (new Domain('d'))
            ->withErrorClass(StubErrorManager::class);
        $method = $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorClass)->toBe(StubErrorManager::class);
    });

    it('propagates requestValidatorClass set on Domain down to Method', function () {
        $domain = (new Domain('d'))
            ->withRequestValidatorClass(StubRequestValidator::class);
        $method = $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->requestValidatorClass)->toBe(StubRequestValidator::class);
    });

    it('propagates allowedRoles set on Domain down to Method', function () {
        $domain = (new Domain('d'))
            ->withAllowedRoles(['admin']);
        $method = $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->allowedRoles)->toBe(['admin']);
    });
});

describe('MethodDefaults cascade — Version level', function () {
    it('propagates errorClass set on Version down to Method', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->withErrorClass(StubErrorManager::class)
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorClass)->toBe(StubErrorManager::class);
    });

    it('propagates requestValidatorClass set on Version down to Method', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->withRequestValidatorClass(StubRequestValidator::class)
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->requestValidatorClass)->toBe(StubRequestValidator::class);
    });
});

describe('MethodDefaults cascade — Service level', function () {
    it('propagates errorClass set on Service down to Method', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->withErrorClass(StubErrorManager::class)
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorClass)->toBe(StubErrorManager::class);
    });

    it('propagates allowedRoles set on Service down to Method', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->withAllowedRoles(['editor'])
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->allowedRoles)->toBe(['editor']);
    });
});

describe('MethodDefaults cascade — Resource level', function () {
    it('Resource with* overrides cascaded Domain default', function () {
        $domain = (new Domain('d'))
            ->withErrorClass(ErrorManager::class);
        $method = $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(StubErrorManager::class)
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorClass)->toBe(StubErrorManager::class);
    });

    it('Resource with* overrides only specified field, leaves others cascaded', function () {
        $domain = (new Domain('d'))
            ->withErrorClass(ErrorManager::class)
            ->withAllowedRoles(['admin']);
        $method = $domain->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(StubErrorManager::class)
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorClass)->toBe(StubErrorManager::class);
        expect($method->allowedRoles)->toBe(['admin']);
    });
});

describe('MethodDefaults — framework fallback defaults', function () {
    it('uses NullRequestValidator when no requestValidatorClass is set anywhere', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->requestValidatorClass)->toBe(NullRequestValidator::class);
    });

    it('uses ErrorManager when no errorClass is set anywhere', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorClass)->toBe(ErrorManager::class);
    });

    it('uses empty allowedRoles when no allowedRoles are set anywhere', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->allowedRoles)->toBe([]);
    });
});

describe('MethodDefaults — isolation: with* on Domain clone does not affect original', function () {
    it('Domain clone is independent', function () {
        $base   = new Domain('d');
        $cloned = $base->withErrorClass(StubErrorManager::class);

        $baseMethod   = $base->version('v1')->service('s')->resource('r')->get(StubApi::class)->findMethod('GET');
        $clonedMethod = $cloned->version('v1')->service('s')->resource('r')->get(StubApi::class)->findMethod('GET');

        expect($baseMethod->errorClass)->toBe(ErrorManager::class);
        expect($clonedMethod->errorClass)->toBe(StubErrorManager::class);
    });
});

describe('allowedRoles strict replacement', function () {
    it('withAllowedRoles([]) marks route as public (empty array, not null)', function () {
        $method = (new Domain('d'))
            ->withAllowedRoles(['admin'])
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->withAllowedRoles([])
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->allowedRoles)->toBe([]);
    });

    it('Service withAllowedRoles replaces Domain roles — no merge', function () {
        $method = (new Domain('d'))
            ->withAllowedRoles(['admin'])
            ->version('v1')
            ->service('svc')
            ->withAllowedRoles(['user'])
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->allowedRoles)->toBe(['user']);
        expect($method->allowedRoles)->not->toContain('admin');
    });
});

describe('MethodDefaults cascade — sibling isolation', function () {
    it('Service-level override does not affect sibling service', function () {
        $domain = (new Domain('d'))->withErrorClass(ErrorManager::class);
        $v1     = $domain->version('v1');

        $m1 = $v1->service('svc-a')
            ->withErrorClass(StubErrorManager::class)
            ->resource('r')
            ->get(StubApi::class)
            ->findMethod('GET');

        $m2 = $v1->service('svc-b')
            ->resource('r')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($m1->errorClass)->toBe(StubErrorManager::class);
        expect($m2->errorClass)->toBe(ErrorManager::class);
    });
});

describe('errorCodes cascade — strict replacement', function () {
    it('propagates errorCodes set on Domain down to Method', function () {
        $method = (new Domain('d'))
            ->withErrorCodes([400, 404])
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorCodes)->toBe([400, 404]);
    });

    it('Service-level errorCodes replace Domain-level — no merge', function () {
        $method = (new Domain('d'))
            ->withErrorCodes([400, 500])
            ->version('v1')
            ->service('svc')
            ->withErrorCodes([422])
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorCodes)->toBe([422]);
        expect($method->errorCodes)->not->toContain(400);
    });

    it('withErrorCodes([]) on Resource stops fall-through (public, no error codes)', function () {
        $method = (new Domain('d'))
            ->withErrorCodes([400, 500])
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorCodes([])
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorCodes)->toBe([]);
    });

    it('uses empty errorCodes by default when nothing is set', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->errorCodes)->toBe([]);
    });
});

describe('authenticated cascade — fall-through', function () {
    it('propagates authenticated set on Domain down to Method', function () {
        $method = (new Domain('d'))
            ->withAuthenticated(true)
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->authenticated)->toBeTrue();
    });

    it('Service-level authenticated overrides Domain-level', function () {
        $method = (new Domain('d'))
            ->withAuthenticated(true)
            ->version('v1')
            ->service('svc')
            ->withAuthenticated(false)
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->authenticated)->toBeFalse();
    });

    it('defaults to false when nothing is set', function () {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->authenticated)->toBeFalse();
    });
});
