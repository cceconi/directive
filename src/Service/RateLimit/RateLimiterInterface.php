<?php

declare(strict_types=1);

namespace Directive\Service\RateLimit;

use Directive\Http\Routing\RateLimit;

/**
 * Checks whether a given request (identified by a composite key) is within
 * its rate limit and returns an immutable result.
 */
interface RateLimiterInterface
{
    /**
     * @param string    $key   Composite rate limit key (e.g. 'rate:ip:1.2.3.4:GET:api/v1/users/items').
     * @param RateLimit $limit Effective rate limit configuration for this request.
     */
    public function check(string $key, RateLimit $limit): RateLimitResult;
}
