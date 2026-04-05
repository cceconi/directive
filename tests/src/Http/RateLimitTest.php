<?php

declare(strict_types=1);

use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\RateLimit\DefaultRateLimitConfig;
use Tests\Helpers\TestConfig;

describe('RateLimitKeyType enum', function (): void {

    it('has three cases with correct string values', function (): void {
        expect(RateLimitKeyType::Ip->value)->toBe('ip');
        expect(RateLimitKeyType::UserId->value)->toBe('user_id');
        expect(RateLimitKeyType::ApiKey->value)->toBe('api_key');
    });

    it('can be created from a valid string', function (): void {
        expect(RateLimitKeyType::from('ip'))->toBe(RateLimitKeyType::Ip);
        expect(RateLimitKeyType::from('user_id'))->toBe(RateLimitKeyType::UserId);
        expect(RateLimitKeyType::from('api_key'))->toBe(RateLimitKeyType::ApiKey);
    });

    it('returns null for invalid value with tryFrom', function (): void {
        expect(RateLimitKeyType::tryFrom('invalid'))->toBeNull();
    });
});

describe('RateLimit value object', function (): void {

    it('stores window, maxRequests, and keyType', function (): void {
        $rl = new RateLimit(window: 60, maxRequests: 100, keyType: RateLimitKeyType::Ip);

        expect($rl->window)->toBe(60);
        expect($rl->maxRequests)->toBe(100);
        expect($rl->keyType)->toBe(RateLimitKeyType::Ip);
    });

    it('fromConfig builds a RateLimit from DefaultRateLimitConfig defaults', function (): void {
        $config = new TestConfig();
        $rateLimitConfig = new DefaultRateLimitConfig($config);
        $config->audit();
        $rl     = RateLimit::fromConfig($rateLimitConfig);

        expect($rl->window)->toBe($rateLimitConfig->getDefaultWindow());
        expect($rl->maxRequests)->toBe($rateLimitConfig->getDefaultMaxRequests());
        expect($rl->keyType)->toBe($rateLimitConfig->getDefaultKeyType());
    });

    it('fromConfig respects env-var overrides', function (): void {
        $_ENV['RATE_LIMIT_WINDOW']       = '120';
        $_ENV['RATE_LIMIT_MAX_REQUESTS']  = '50';
        $_ENV['RATE_LIMIT_KEY_TYPE']      = 'user_id';

        $config = new TestConfig();
        $rateLimitConfig = new DefaultRateLimitConfig($config);
        $config->audit();
        $rl     = RateLimit::fromConfig($rateLimitConfig);

        expect($rl->window)->toBe(120);
        expect($rl->maxRequests)->toBe(50);
        expect($rl->keyType)->toBe(RateLimitKeyType::UserId);

        unset($_ENV['RATE_LIMIT_WINDOW'], $_ENV['RATE_LIMIT_MAX_REQUESTS'], $_ENV['RATE_LIMIT_KEY_TYPE']);
    });
});
