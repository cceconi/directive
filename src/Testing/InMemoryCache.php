<?php

declare(strict_types=1);

namespace Directive\Testing;

use Psr\SimpleCache\CacheInterface;

/**
 * In-memory PSR-16 cache for tests.
 *
 * Supports optional TTL (int seconds or DateInterval). Expired entries are
 * treated as absent. Safe for single-process test use only.
 */
final class InMemoryCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expiresAt: float|null}> */
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->store[$key]['value'];
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $expiresAt = null;

        if ($ttl instanceof \DateInterval) {
            $expiresAt = microtime(true) + (float) (new \DateTime())->add($ttl)->getTimestamp() - (float) (new \DateTime())->getTimestamp();
        } elseif (is_int($ttl)) {
            $expiresAt = microtime(true) + $ttl;
        }

        $this->store[$key] = ['value' => $value, 'expiresAt' => $expiresAt];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->store = [];
        return true;
    }

    public function has(string $key): bool
    {
        if (!array_key_exists($key, $this->store)) {
            return false;
        }

        $expiresAt = $this->store[$key]['expiresAt'];

        if ($expiresAt !== null && microtime(true) > $expiresAt) {
            unset($this->store[$key]);
            return false;
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }
}
