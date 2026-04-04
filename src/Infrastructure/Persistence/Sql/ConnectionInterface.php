<?php

declare(strict_types=1);

namespace Directive\Infrastructure\Persistence\Sql;

/**
 * Agnostic SQL connection contract.
 *
 * The framework owns this interface; the application provides an adapter
 * (e.g. DbalConnectionAdapter wrapping Doctrine\DBAL\Connection).
 * This keeps directive/composer.json free of any doctrine/dbal dependency.
 */
interface ConnectionInterface
{
    /**
     * Execute a SELECT query and return the first row, or null if not found.
     *
     * @param array<mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOne(string $sql, array $params = []): ?array;

    /**
     * Execute a SELECT query and return all rows.
     *
     * @param array<mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * Execute an INSERT, UPDATE or DELETE statement.
     * Returns the number of affected rows.
     *
     * @param array<mixed> $params
     */
    public function execute(string $sql, array $params = []): int;
}
