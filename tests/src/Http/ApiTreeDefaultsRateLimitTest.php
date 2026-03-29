<?php

declare(strict_types=1);

use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\Business\ErrorManager;
use Directive\Http\Validator\NullRequestValidator;
use Tests\Helpers\StubApi;

describe('API-tree rate-limit cascade', function (): void {

    it('propagates RateLimit set on Domain down to Method', function (): void {
        $rl = new RateLimit(60, 100, RateLimitKeyType::Ip);

        $method = (new Domain('d'))
            ->withRateLimit($rl)
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->rateLimit)->toBe($rl);
    });

    it('propagates rateLimitKeyType set on Domain down to Method', function (): void {
        $method = (new Domain('d'))
            ->withRateLimitKeyType(RateLimitKeyType::UserId)
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->rateLimitKeyType)->toBe(RateLimitKeyType::UserId);
    });

    it('propagates rateLimitEnabled=false set on Domain down to Method', function (): void {
        $method = (new Domain('d'))
            ->withRateLimitEnabled(false)
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->rateLimitEnabled)->toBeFalse();
    });

    it('per-resource withRateLimit overrides Domain-level default', function (): void {
        $domainRl   = new RateLimit(60, 100, RateLimitKeyType::Ip);
        $resourceRl = new RateLimit(30, 10, RateLimitKeyType::ApiKey);

        $method = (new Domain('d'))
            ->withRateLimit($domainRl)
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withRateLimit($resourceRl)
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->rateLimit)->toBe($resourceRl);
    });

    it('null rateLimit stays null when not set anywhere', function (): void {
        $method = (new Domain('d'))
            ->version('v1')
            ->service('svc')
            ->resource('res')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->get(StubApi::class)
            ->findMethod('GET');

        expect($method->rateLimit)->toBeNull();
        expect($method->rateLimitKeyType)->toBeNull();
        expect($method->rateLimitEnabled)->toBeNull();
    });
});
