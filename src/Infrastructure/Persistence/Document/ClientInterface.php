<?php

declare(strict_types=1);

namespace Directive\Infrastructure\Persistence\Document;

/**
 * Agnostic document store contract.
 *
 * The framework owns this interface; the application provides an adapter
 * (e.g. MongoClientAdapter wrapping mongodb/mongodb).
 * This keeps directive/composer.json free of any document SDK dependency.
 */
interface ClientInterface
{
    /**
     * Find the first document matching the filter in the given collection.
     *
     * @param array<string, mixed> $filter
     * @return array<string, mixed>|null
     */
    public function findOne(string $collection, array $filter): ?array;

    /**
     * Find all documents matching the filter in the given collection.
     *
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $options  Driver-specific options (sort, limit…)
     * @return array<int, array<string, mixed>>
     */
    public function findMany(string $collection, array $filter, array $options = []): array;

    /**
     * Insert or replace a document matching the filter.
     *
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $data
     */
    public function upsert(string $collection, array $filter, array $data): void;

    /**
     * Delete all documents matching the filter.
     *
     * @param array<string, mixed> $filter
     */
    public function remove(string $collection, array $filter): void;
}
