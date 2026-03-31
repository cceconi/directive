<?php

declare(strict_types=1);

namespace Directive\Testing;

use Directive\Http\Routing\RateLimit;
use Directive\Service\RateLimit\RateLimiterInterface;
use Directive\Service\RateLimit\RateLimitResult;

/**
 * Fake RateLimiter for tests.
 *
 * Defaults to pass (all requests allowed). Switch to block mode via block().
 * Records every check() call for assertion.
 */
final class FakeRateLimiter implements RateLimiterInterface
{
    private bool $blocked = false;

    /** @var list<array{key: string, limit: RateLimit}> */
    private array $calls = [];

    public function check(string $key, RateLimit $limit): RateLimitResult
    {
        $this->calls[] = ['key' => $key, 'limit' => $limit];

        if ($this->blocked) {
            return RateLimitResult::rejected(resetIn: 60);
        }

        return RateLimitResult::allowed(remaining: $limit->maxRequests - 1, resetIn: $limit->window);
    }

    /**
     * Switch to block mode — all subsequent check() calls return rejected.
     */
    public function block(): static
    {
        $this->blocked = true;
        return $this;
    }

    /**
     * Switch to pass mode (default) — all subsequent check() calls return allowed.
     */
    public function pass(): static
    {
        $this->blocked = false;
        return $this;
    }

    /**
     * Return the number of times check() has been called.
     */
    public function getCallCount(): int
    {
        return count($this->calls);
    }

    /**
     * Assert check() was called exactly $times times.
     */
    public function assertChecked(int $times): void
    {
        expect($this->getCallCount())->toBe($times);
    }

    /**
     * Assert check() was called at least once with the given key.
     */
    public function assertCheckedWithKey(string $key): void
    {
        $keys = array_column($this->calls, 'key');
        expect($keys)->toContain($key);
    }

    /**
     * Reset call recording and restore pass mode.
     */
    public function reset(): void
    {
        $this->calls   = [];
        $this->blocked = false;
    }
}
