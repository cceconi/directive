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
 *   - 'rate_limit' → DIRECTIVE_RATE_LIMIT_ENABLED (default: true)
 *     Master switch for the rate limiting middleware. When false, RateLimitMiddleware
 *     skips all checks. Per-route rateLimitEnabled=true can still force-enable a route
 *     even when the global switch is off.
 */
class DirectiveFeatures extends AbstractFeatures
{
    protected function define(): void
    {
        $this->feature('rate_limit', 'DIRECTIVE_RATE_LIMIT_ENABLED', default: true);
    }
}
