<?php

declare(strict_types=1);

use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\RateLimit\DefaultRateLimitConfig;
use Directive\Service\RateLimit\RateLimitResult;
use Directive\Service\RateLimit\RedisRateLimiter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tests\Helpers\TestConfig;

/**
 * Tests for RedisRateLimiter using an in-memory ArrayAdapter cache.
 * No real Redis connection is required.
 */
describe('RedisRateLimiter (in-memory cache)', function (): void {

    function makeRateLimiter(): RedisRateLimiter
    {
        $config = new TestConfig();
        return new RedisRateLimiter(
            config: new DefaultRateLimitConfig($config),
            cachePool: new ArrayAdapter(),
        );
    }

    it('allows the first request', function (): void {
        $limiter = makeRateLimiter();
        $limit   = new RateLimit(window: 60, maxRequests: 5, keyType: RateLimitKeyType::Ip);

        $result = $limiter->check('user:1', $limit);

        expect($result)->toBeInstanceOf(RateLimitResult::class);
        expect($result->allowed)->toBeTrue();
        expect($result->remaining)->toBe(4); // 5 - 1
    });

    it('counts down remaining tokens correctly', function (): void {
        $limiter = makeRateLimiter();
        $limit   = new RateLimit(window: 60, maxRequests: 3, keyType: RateLimitKeyType::Ip);

        $r1 = $limiter->check('user:cnt', $limit);
        $r2 = $limiter->check('user:cnt', $limit);

        expect($r1->allowed)->toBeTrue();
        expect($r1->remaining)->toBe(2);
        expect($r2->allowed)->toBeTrue();
        expect($r2->remaining)->toBe(1);
    });

    it('rejects requests at the limit', function (): void {
        $limiter = makeRateLimiter();
        $limit   = new RateLimit(window: 60, maxRequests: 2, keyType: RateLimitKeyType::Ip);

        $limiter->check('user:limit', $limit);
        $limiter->check('user:limit', $limit);
        $rejected = $limiter->check('user:limit', $limit);

        expect($rejected->allowed)->toBeFalse();
        expect($rejected->remaining)->toBe(0);
        expect($rejected->resetIn)->toBeGreaterThanOrEqual(0);
    });

    it('different keys are tracked independently', function (): void {
        $limiter = makeRateLimiter();
        $limit   = new RateLimit(window: 60, maxRequests: 1, keyType: RateLimitKeyType::Ip);

        $result1 = $limiter->check('user:A', $limit);
        $result2 = $limiter->check('user:B', $limit);

        expect($result1->allowed)->toBeTrue();
        expect($result2->allowed)->toBeTrue();
    });

    it('factories are cached per (window, maxRequests) pair', function (): void {
        $limiter = makeRateLimiter();
        $limit60 = new RateLimit(window: 60, maxRequests: 10, keyType: RateLimitKeyType::Ip);
        $limit30 = new RateLimit(window: 30, maxRequests: 10, keyType: RateLimitKeyType::Ip);

        // Both should succeed (separate buckets)
        $r1 = $limiter->check('same-key', $limit60);
        $r2 = $limiter->check('same-key', $limit30);

        expect($r1->allowed)->toBeTrue();
        expect($r2->allowed)->toBeTrue();
    });
});
