<?php

declare(strict_types=1);

use Directive\Application\Exception\AccessDeniedException;
use Directive\Application\Exception\BusinessRuleException;
use Directive\Application\Exception\ConflictException;
use Directive\Application\Exception\EntityNotFoundException;
use Directive\Application\Exception\ValidationException;
use Directive\Http\Endpoint\AbstractApi;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Validator\NullRequestValidator;
use Directive\Service\Business\ErrorManager;

// ---------------------------------------------------------------------------
// ThrowingApi: a configurable AbstractApi stub that throws in compute()
// ---------------------------------------------------------------------------

final class ThrowingApi extends AbstractApi
{
    private static ?\Throwable $nextException = null;

    public static function willThrow(\Throwable $e): void
    {
        self::$nextException = $e;
    }

    protected function compute(): void
    {
        $e = self::$nextException;
        self::$nextException = null;

        if ($e !== null) {
            throw $e;
        }
    }
}

// ---------------------------------------------------------------------------
// Route builder helper
// ---------------------------------------------------------------------------

function makeThrowingManager(): ApiDefinitionManager
{
    $domain = new Domain('test');
    $domain->version('v1')
        ->resource('throw')
        ->withErrorClass(ErrorManager::class)
        ->withRequestValidatorClass(NullRequestValidator::class)
        ->get(ThrowingApi::class);

    $manager = new ApiDefinitionManager();
    $manager->registerDomain($domain);
    return $manager;
}

// ---------------------------------------------------------------------------
// DomainExceptionMappingTest
// ---------------------------------------------------------------------------

describe('Router domain exception mapping', function () {
    it('maps EntityNotFoundException to HTTP 404', function () {
        ThrowingApi::willThrow(new EntityNotFoundException('Not found'));

        $router   = $this->buildRouter(makeThrowingManager());
        $response = $this->dispatch($router, 'GET', 'test', 'v1', resource: 'throw');
        expect($response->getStatusCode())->toBe(404);
    });

    it('maps AccessDeniedException to HTTP 403', function () {
        ThrowingApi::willThrow(new AccessDeniedException('Forbidden'));

        $router   = $this->buildRouter(makeThrowingManager());
        $response = $this->dispatch($router, 'GET', 'test', 'v1', resource: 'throw');
        expect($response->getStatusCode())->toBe(403);
    });

    it('maps ConflictException to HTTP 409', function () {
        ThrowingApi::willThrow(new ConflictException('Conflict'));

        $router   = $this->buildRouter(makeThrowingManager());
        $response = $this->dispatch($router, 'GET', 'test', 'v1', resource: 'throw');
        expect($response->getStatusCode())->toBe(409);
    });

    it('maps ValidationException to HTTP 422', function () {
        ThrowingApi::willThrow(new ValidationException(errors: ['email' => 'Invalid email']));

        $router   = $this->buildRouter(makeThrowingManager());
        $response = $this->dispatch($router, 'GET', 'test', 'v1', resource: 'throw');
        expect($response->getStatusCode())->toBe(422);
    });

    it('maps ValidationException errors into the response body', function () {
        ThrowingApi::willThrow(new ValidationException(errors: ['email' => 'Invalid email']));

        $router   = $this->buildRouter(makeThrowingManager());
        $response = $this->dispatch($router, 'GET', 'test', 'v1', resource: 'throw');
        $body     = json_decode((string) $response->getBody(), associative: true) ?? [];

        $errors = $body['errors'] ?? [];
        expect($errors)->toContain(['field' => 'email', 'message' => 'Invalid email']);
    });

    it('maps BusinessRuleException to HTTP 422', function () {
        ThrowingApi::willThrow(new BusinessRuleException('Rule violated'));

        $router   = $this->buildRouter(makeThrowingManager());
        $response = $this->dispatch($router, 'GET', 'test', 'v1', resource: 'throw');
        expect($response->getStatusCode())->toBe(422);
    });

    it('maps a plain \DomainException to HTTP 400', function () {
        ThrowingApi::willThrow(new \DomainException('Bad domain call'));

        $router   = $this->buildRouter(makeThrowingManager());
        $response = $this->dispatch($router, 'GET', 'test', 'v1', resource: 'throw');
        expect($response->getStatusCode())->toBe(400);
    });
});
