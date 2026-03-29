<?php

declare(strict_types=1);

use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\RateLimit\NullRateLimiter;
use Directive\Service\RateLimit\RateLimitResult;

describe('NullRateLimiter', function (): void {

    it('always returns an allowed result', function (): void {
        $limiter = new NullRateLimiter();
        $limit   = new RateLimit(window: 60, maxRequests: 100, keyType: RateLimitKeyType::Ip);

        $result = $limiter->check('any-key', $limit);

        expect($result)->toBeInstanceOf(RateLimitResult::class);
        expect($result->allowed)->toBeTrue();
    });

    it('returns PHP_INT_MAX remaining tokens', function (): void {
        $limiter = new NullRateLimiter();
        $limit   = new RateLimit(window: 60, maxRequests: 5, keyType: RateLimitKeyType::UserId);

        $result = $limiter->check('user:42', $limit);

        expect($result->remaining)->toBe(PHP_INT_MAX);
    });

    it('always returns resetIn of 0', function (): void {
        $limiter = new NullRateLimiter();
        $limit   = new RateLimit(window: 3600, maxRequests: 1000, keyType: RateLimitKeyType::ApiKey);

        $result = $limiter->check('key:abc', $limit);

        expect($result->resetIn)->toBe(0);
    });

    it('is idempotent regardless of key or limit', function (): void {
        $limiter = new NullRateLimiter();
        $limit   = new RateLimit(1, 1, RateLimitKeyType::Ip);

        for ($i = 0; $i < 200; $i++) {
            $result = $limiter->check('overloaded-key', $limit);
            expect($result->allowed)->toBeTrue();
        }
    });
});
