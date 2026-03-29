<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

use Directive\Service\RateLimit\RateLimitConfigInterface;

/**
 * Immutable value object encapsulating the rate limit configuration for one route.
 *
 * Declared via withRateLimit() on Domain / Version / Service / Resource to
 * override the global defaults from RateLimitConfigInterface for that subtree.
 */
readonly class RateLimit
{
    /**
     * @param int              $window      Sliding window duration in seconds (must be > 0).
     * @param int              $maxRequests Maximum requests allowed in the window (must be > 0).
     * @param RateLimitKeyType $keyType     Identity strategy used to derive the counter key.
     */
    public function __construct(
        public int $window,
        public int $maxRequests,
        public RateLimitKeyType $keyType,
    ) {}

    /**
     * Build a RateLimit from the global configuration defaults.
     * Used by RateLimitMiddleware when Method::$rateLimit === null.
     */
    public static function fromConfig(RateLimitConfigInterface $config): self
    {
        return new self(
            window: $config->getDefaultWindow(),
            maxRequests: $config->getDefaultMaxRequests(),
            keyType: $config->getDefaultKeyType(),
        );
    }
}
