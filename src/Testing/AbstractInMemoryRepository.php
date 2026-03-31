<?php

declare(strict_types=1);

namespace Directive\Testing;

/**
 * Generic in-memory repository base for test doubles.
 *
 * Extend this class in tests to build typed fakes without a real database.
 *
 * @template T
 */
abstract class AbstractInMemoryRepository
{
    /** @var array<string, T> */
    private array $store = [];

    /**
     * Return the unique string identifier for the given entity.
     *
     * @param T $entity
     */
    abstract protected function getId(mixed $entity): string;

    /**
     * Persist (insert or replace) an entity.
     *
     * @param T $entity
     */
    protected function save(mixed $entity): void
    {
        $this->store[$this->getId($entity)] = $entity;
    }

    /**
     * Find an entity by its string identifier.
     *
     * @return T|null
     */
    protected function findById(string $id): mixed
    {
        return $this->store[$id] ?? null;
    }

    /**
     * Return all stored entities (re-indexed, no gaps).
     *
     * @return list<T>
     */
    protected function findAll(): array
    {
        return array_values($this->store);
    }

    /**
     * Remove an entity by its identifier (no-op if not found).
     */
    protected function delete(string $id): void
    {
        unset($this->store[$id]);
    }

    /**
     * Return the number of stored entities.
     */
    protected function count(): int
    {
        return count($this->store);
    }

    /**
     * Remove all stored entities.
     */
    protected function clear(): void
    {
        $this->store = [];
    }
}
