<?php

declare(strict_types=1);

use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Testing\FakeRateLimiter;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeRateLimit(): RateLimit
{
    return new RateLimit(window: 60, maxRequests: 100, keyType: RateLimitKeyType::Ip);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('FakeRateLimiter', function (): void {

    beforeEach(function (): void {
        $this->limiter = new FakeRateLimiter();
    });

    it('pass mode (default) — check() returns allowed result', function (): void {
        $result = $this->limiter->check('rate:ip:1.2.3.4', makeRateLimit());
        expect($result->allowed)->toBeTrue();
    });

    it('block mode — check() returns rejected result', function (): void {
        $result = $this->limiter->block()->check('rate:ip:1.2.3.4', makeRateLimit());
        expect($result->allowed)->toBeFalse();
    });

    it('pass() restores allowed mode after block()', function (): void {
        $this->limiter->block();
        $this->limiter->pass();
        $result = $this->limiter->check('rate:ip:1.2.3.4', makeRateLimit());
        expect($result->allowed)->toBeTrue();
    });

    it('assertChecked() passes when call count matches', function (): void {
        $this->limiter->check('key1', makeRateLimit());
        $this->limiter->check('key2', makeRateLimit());
        $this->limiter->assertChecked(2); // must not throw
    });

    it('assertCheckedWithKey() passes when key was used', function (): void {
        $this->limiter->check('rate:ip:10.0.0.1', makeRateLimit());
        $this->limiter->assertCheckedWithKey('rate:ip:10.0.0.1'); // must not throw
    });

    it('reset() clears call history and restores pass mode', function (): void {
        $this->limiter->block()->check('key', makeRateLimit());
        $this->limiter->reset();

        expect($this->limiter->getCallCount())->toBe(0);
        $result = $this->limiter->check('key', makeRateLimit());
        expect($result->allowed)->toBeTrue();
    });
});
