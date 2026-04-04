<?php

declare(strict_types=1);

namespace Directive\Infrastructure\Persistence\KeyValue;

use Directive\Exception\PersistenceException;

/**
 * Base class for key-value repositories using a framework-agnostic StorageInterface.
 *
 * This seam targets persistent business data (long-lived sessions, distributed
 * counters, application state). It is distinct from symfony/cache which handles
 * ephemeral, reconstructible cache.
 *
 * The application provides a concrete adapter (e.g. RedisStorageAdapter) that
 * implements StorageInterface and wraps the SDK of its choice.
 *
 * -------------------------------------------------------------------------
 * Atomicity and eventual consistency
 * -------------------------------------------------------------------------
 * This framework does not expose a transact() or multi-exec() method.
 * Redis MULTI/EXEC, Valkey pipelining, or DynamoDB conditional writes are
 * out of scope for the framework — implement them in the application adapter.
 *
 * DDD recommendation: for cross-aggregate consistency with key-value stores,
 * publish domain events and let handlers apply changes asynchronously
 * (eventual consistency model).
 * -------------------------------------------------------------------------
 *
 * Usage:
 *   final class SessionRepository extends AbstractKeyValueRepository
 *       implements SessionRepositoryInterface
 *   {
 *       public function findSession(string $token): ?SessionData
 *       {
 *           $raw = $this->get('session:' . $token);
 *           return $raw !== null ? SessionData::fromArray((array) $raw) : null;
 *       }
 *   }
 */
abstract class AbstractKeyValueRepository
{
    public function __construct(
        protected readonly StorageInterface $storage,
    ) {}

    /**
     * @throws PersistenceException
     */
    protected function get(string $key): mixed
    {
        try {
            return $this->storage->get($key);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws PersistenceException
     */
    protected function set(string $key, mixed $value, ?int $ttl = null): void
    {
        try {
            $this->storage->set($key, $value, $ttl);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws PersistenceException
     */
    protected function delete(string $key): void
    {
        try {
            $this->storage->delete($key);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @throws PersistenceException
     */
    protected function exists(string $key): bool
    {
        try {
            return $this->storage->exists($key);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }
}
