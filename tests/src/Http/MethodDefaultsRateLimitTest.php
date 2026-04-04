<?php

declare(strict_types=1);

use Directive\Http\Routing\MethodDefaults;
use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;

describe('MethodDefaults — rate-limit with* methods', function (): void {

    it('rateLimit, rateLimitKeyType, rateLimitEnabled start as null', function (): void {
        $d = new MethodDefaults();

        expect($d->rateLimit)->toBeNull();
        expect($d->rateLimitKeyType)->toBeNull();
        expect($d->rateLimitEnabled)->toBeNull();
    });

    it('withRateLimit returns new instance and preserves other fields', function (): void {
        $rl       = new RateLimit(60, 100, RateLimitKeyType::Ip);
        $original = new MethodDefaults()->withErrorClass('App\\Errors');
        $updated  = $original->withRateLimit($rl);

        expect($updated->rateLimit)->toBe($rl);
        expect($original->rateLimit)->toBeNull();         // immutable
        expect($updated->errorClass)->toBe('App\\Errors'); // preserved
    });

    it('withRateLimitKeyType returns new instance', function (): void {
        $original = new MethodDefaults();
        $updated  = $original->withRateLimitKeyType(RateLimitKeyType::UserId);

        expect($updated->rateLimitKeyType)->toBe(RateLimitKeyType::UserId);
        expect($original->rateLimitKeyType)->toBeNull();
    });

    it('withRateLimitEnabled returns new instance', function (): void {
        $original = new MethodDefaults();
        $updated  = $original->withRateLimitEnabled(false);

        expect($updated->rateLimitEnabled)->toBeFalse();
        expect($original->rateLimitEnabled)->toBeNull();
    });

    it('other with* methods carry rateLimit fields through', function (): void {
        $rl = new RateLimit(30, 20, RateLimitKeyType::ApiKey);
        $d  = new MethodDefaults()
            ->withRateLimit($rl)
            ->withRateLimitEnabled(true)
            ->withErrorClass('App\\Errors'); // triggers internal new self(...)

        expect($d->rateLimit)->toBe($rl);
        expect($d->rateLimitEnabled)->toBeTrue();
    });
});
