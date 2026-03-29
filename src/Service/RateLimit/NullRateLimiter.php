<?php

declare(strict_types=1);

namespace Directive\Service\RateLimit;

use Directive\Http\Routing\RateLimit;

/**
 * No-op rate limiter — always allows requests without any I/O.
 *
 * Used as the default DI binding when redis is not configured, and
 * in tests to avoid Redis dependencies.
 */
final class NullRateLimiter implements RateLimiterInterface
{
    public function check(string $key, RateLimit $limit): RateLimitResult
    {
        return RateLimitResult::allowed(remaining: PHP_INT_MAX, resetIn: 0);
    }
}
