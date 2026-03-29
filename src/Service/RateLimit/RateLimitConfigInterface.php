<?php

declare(strict_types=1);

namespace Directive\Service\RateLimit;

use Directive\Http\Routing\RateLimitKeyType;

/**
 * Infrastructure configuration for the rate limiter.
 *
 * Provides global defaults applied to every route where Method::$rateLimit === null.
 * A local RateLimit declared via withRateLimit() on a tree node overrides these
 * defaults entirely for that subtree — same strict-replacement semantics as allowedRoles.
 *
 * The enable/disable master switch is handled separately by DirectiveFeatures::isEnabled('rate_limit').
 * This interface carries only technical configuration, not the boolean toggle.
 */
interface RateLimitConfigInterface
{
    /** Redis connection DSN (e.g. 'tcp://127.0.0.1:6379'). */
    public function getRedisDsn(): string;

    /** Default sliding window duration in seconds. */
    public function getDefaultWindow(): int;

    /** Default maximum requests allowed per window. */
    public function getDefaultMaxRequests(): int;

    /** Default identity strategy for key derivation. */
    public function getDefaultKeyType(): RateLimitKeyType;
}
