<?php

declare(strict_types=1);

use Directive\Http\Routing\Method;
use Directive\Http\Validator\NullRequestValidator;
use Directive\Service\Business\ErrorManager;
use Tests\Helpers\StubApi;

describe('Method — construction defaults', function () {
    it('defaults new optional properties to empty / false / null', function () {
        $method = new Method(
            httpMethod: 'GET',
            apiClass: StubApi::class,
            requestValidatorClass: NullRequestValidator::class,
            errorClass: ErrorManager::class,
        );

        expect($method->requestSchema)->toBeNull();
        expect($method->responseSchema)->toBeNull();
        expect($method->errorCodes)->toBe([]);
        expect($method->authenticated)->toBeFalse();
    });

    it('accepts all new properties when provided', function () {
        $method = new Method(
            httpMethod: 'POST',
            apiClass: StubApi::class,
            requestValidatorClass: NullRequestValidator::class,
            errorClass: ErrorManager::class,
            requestSchema: 'App\\Web\\Request\\CreateUserRequest',
            responseSchema: 'schemas/create-user-response.json',
            errorCodes: [400, 422, 500],
            authenticated: true,
        );

        expect($method->requestSchema)->toBe('App\\Web\\Request\\CreateUserRequest');
        expect($method->responseSchema)->toBe('schemas/create-user-response.json');
        expect($method->errorCodes)->toBe([400, 422, 500]);
        expect($method->authenticated)->toBeTrue();
    });

    it('is backward-compatible: existing params still work without new ones', function () {
        $method = new Method(
            httpMethod: 'DELETE',
            apiClass: StubApi::class,
            requestValidatorClass: NullRequestValidator::class,
            errorClass: ErrorManager::class,
            allowedRoles: ['admin'],
            responseEntityClass: null,
        );

        expect($method->httpMethod)->toBe('DELETE');
        expect($method->allowedRoles)->toBe(['admin']);
        expect($method->errorCodes)->toBe([]);
        expect($method->authenticated)->toBeFalse();
    });
});
