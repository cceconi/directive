<?php

declare(strict_types=1);

namespace Directive\Service\RateLimit;

/**
 * Immutable result returned by RateLimiterInterface::check().
 */
readonly class RateLimitResult
{
    private function __construct(
        public bool $allowed,
        public int $remaining,
        public int $resetIn,
    ) {}

    /**
     * Build a result for an allowed request.
     *
     * @param int $remaining Requests remaining in the current window.
     * @param int $resetIn   Seconds until the window resets (informational).
     */
    public static function allowed(int $remaining, int $resetIn): self
    {
        return new self(allowed: true, remaining: $remaining, resetIn: $resetIn);
    }

    /**
     * Build a result for a rejected request.
     *
     * @param int $resetIn Seconds until requests are allowed again (used for Retry-After header).
     */
    public static function rejected(int $resetIn): self
    {
        return new self(allowed: false, remaining: 0, resetIn: $resetIn);
    }
}
