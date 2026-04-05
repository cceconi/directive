<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

/**
 * Concrete feature-flag set for the Directive framework.
 *
 * Registers all framework-level feature flags. Applications may subclass this
 * to add application-specific flags while reusing the framework binding.
 *
 * Current flags:
 *   - 'rate_limit'     → RATE_LIMIT_ENABLED (default: true)
 *     Master switch for the rate limiting middleware. When false, RateLimitMiddleware
 *     skips all checks. Per-route rateLimitEnabled=true can still force-enable a route
 *     even when the global switch is off.
 *   - 'debug_logging'  → DEBUG_LOGGING (default: false)
 *     When enabled, HttpDebugLoggingMiddleware is added to the stack and emits
 *     a DEBUG-level record for every incoming HTTP request and outgoing response.
 */
class DirectiveFeatures extends AbstractFeatures
{
    protected function define(): void
    {
        $this->feature('rate_limit', 'RATE_LIMIT_ENABLED', default: true);
        $this->feature('debug_logging', 'DEBUG_LOGGING', default: false);
    }
}
