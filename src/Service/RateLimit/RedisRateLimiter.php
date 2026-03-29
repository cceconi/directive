<?php

declare(strict_types=1);

namespace Directive\Service\RateLimit;

use Directive\Http\Routing\RateLimit;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

/**
 * Redis-backed rate limiter using symfony/rate-limiter's SlidingWindowLimiter.
 *
 * Delegates to a RateLimiterFactory configured with a SlidingWindowLimiter policy
 * and a CacheStorage backed by a Redis CacheItemPoolInterface.
 *
 * The Redis connection is created lazily on the first check() call. Multiple
 * (window, maxRequests) combinations are supported via a per-policy factory cache.
 *
 * For testing, inject an in-memory CacheItemPoolInterface (e.g. ArrayAdapter) to
 * avoid a real Redis dependency.
 */
final class RedisRateLimiter implements RateLimiterInterface
{
    /** @var array<string, RateLimiterFactory> */
    private array $factories = [];

    private ?CacheItemPoolInterface $cachePool;

    private ?CacheStorage $storage = null;

    public function __construct(
        private readonly RateLimitConfigInterface $config,
        ?CacheItemPoolInterface $cachePool = null,
    ) {
        $this->cachePool = $cachePool;
    }

    public function check(string $key, RateLimit $limit): RateLimitResult
    {
        $limiter   = $this->resolveFactory($limit)->create($key);
        $rateLimit = $limiter->consume(1);

        $retryAfter = $rateLimit->getRetryAfter();
        $resetIn    = max(0, $retryAfter->getTimestamp() - time());

        if ($rateLimit->isAccepted()) {
            return RateLimitResult::allowed($rateLimit->getRemainingTokens(), $resetIn);
        }

        return RateLimitResult::rejected($resetIn);
    }

    private function resolveFactory(RateLimit $limit): RateLimiterFactory
    {
        $cacheKey = sprintf('%d:%d', $limit->window, $limit->maxRequests);

        if (!isset($this->factories[$cacheKey])) {
            $this->factories[$cacheKey] = new RateLimiterFactory(
                config: [
                    'id'       => sprintf('directive_rl_%d_%d', $limit->window, $limit->maxRequests),
                    'policy'   => 'sliding_window',
                    'limit'    => $limit->maxRequests,
                    'interval' => sprintf('%d seconds', $limit->window),
                ],
                storage: $this->getStorage(),
            );
        }

        return $this->factories[$cacheKey];
    }

    private function getStorage(): CacheStorage
    {
        if ($this->storage === null) {
            $pool = $this->cachePool;

            if ($pool === null) {
                $connection = RedisAdapter::createConnection($this->config->getRedisDsn());
                $pool       = new RedisAdapter($connection);
            }

            $this->storage = new CacheStorage($pool);
        }

        return $this->storage;
    }
}
