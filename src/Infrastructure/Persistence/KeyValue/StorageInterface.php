<?php

declare(strict_types=1);

namespace Directive\Infrastructure\Persistence\KeyValue;

/**
 * Agnostic key-value storage contract for persistent business data.
 *
 * This interface is NOT a cache abstraction. It targets persistent key-value
 * stores (Redis in AOF/RDB mode, Valkey, DynamoDB…) for business data such
 * as long-lived sessions, distributed counters, and application state.
 *
 * For ephemeral, reconstructible cache (rate-limit counters, HTTP cache…)
 * use symfony/cache instead.
 *
 * The framework owns this interface; the application provides an adapter
 * (e.g. RedisStorageAdapter wrapping Predis or PhpRedis).
 * This keeps directive/composer.json free of any key-value SDK dependency.
 */
interface StorageInterface
{
    /**
     * Retrieve the value stored at the given key, or null if absent.
     */
    public function get(string $key): mixed;

    /**
     * Store a value at the given key, with an optional TTL in seconds.
     * Passing null as TTL means the value persists indefinitely.
     */
    public function set(string $key, mixed $value, ?int $ttl = null): void;

    /**
     * Delete the value stored at the given key.
     */
    public function delete(string $key): void;

    /**
     * Return true if a value exists for the given key.
     */
    public function exists(string $key): bool;
}
