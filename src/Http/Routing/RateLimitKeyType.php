<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

/**
 * Strategy used to derive the identity key for rate limiting.
 *
 * Ip       — rate limit per client IP address (works for public and authenticated routes).
 * UserId   — rate limit per authenticated user ID (requires Auth middleware to have run).
 * ApiKey   — rate limit per API key extracted from the Authorization Bearer token.
 */
enum RateLimitKeyType: string
{
    case Ip     = 'ip';
    case UserId = 'user_id';
    case ApiKey = 'api_key';
}
