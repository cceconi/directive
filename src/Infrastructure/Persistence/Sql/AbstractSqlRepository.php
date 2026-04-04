<?php

declare(strict_types=1);

namespace Directive\Infrastructure\Persistence\Sql;

use Directive\Exception\PersistenceException;

/**
 * Base class for SQL repositories using a framework-agnostic ConnectionInterface.
 *
 * -------------------------------------------------------------------------
 * Anti-ORM decision
 * -------------------------------------------------------------------------
 * Doctrine ORM is explicitly out of scope. Domain entities (extending
 * Directive\Application\Model\AbstractEntity) must remain free of mapping
 * annotations — a core DDD principle. This class targets doctrine/dbal
 * (standalone, without ORM) via a DbalConnectionAdapter, or any other SQL
 * driver through a custom ConnectionInterface implementation.
 *
 * The application writes native SQL inside concrete repositories; this class
 * only provides injection, exception wrapping, and pattern documentation.
 * -------------------------------------------------------------------------
 *
 * Usage:
 *   final class UserRepository extends AbstractSqlRepository
 *       implements UserRepositoryInterface
 *   {
 *       public function findByEmail(string $email): ?User
 *       {
 *           $row = $this->fetchOne(
 *               'SELECT * FROM users WHERE email = ?',
 *               [$email]
 *           );
 *           return $row !== null ? User::fromRow($row) : null;
 *       }
 *   }
 */
abstract class AbstractSqlRepository
{
    public function __construct(
        protected readonly ConnectionInterface $connection,
    ) {}

    /**
     * Execute a SELECT and return the first matching row, or null.
     *
     * @param array<mixed> $params
     * @return array<string, mixed>|null
     * @throws PersistenceException
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            return $this->connection->fetchOne($sql, $params);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a SELECT and return all matching rows.
     *
     * @param array<mixed> $params
     * @return array<int, array<string, mixed>>
     * @throws PersistenceException
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        try {
            return $this->connection->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute an INSERT, UPDATE or DELETE.
     * Returns the number of affected rows.
     *
     * @param array<mixed> $params
     * @throws PersistenceException
     */
    protected function execute(string $sql, array $params = []): int
    {
        try {
            return $this->connection->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a callable inside a database transaction.
     *
     * Use only for writes belonging to the **same aggregate root**.
     * If you need to coordinate two aggregate roots, use domain events instead.
     *
     * Note: The callable must not raise application-layer exceptions
     * (EntityNotFoundException, AccessDeniedException, etc.). Any \Throwable
     * thrown inside the callable will trigger a rollback and will be wrapped
     * in a PersistenceException.
     *
     * @param callable(): mixed $fn
     * @return mixed
     * @throws PersistenceException
     */
    protected function transact(callable $fn): mixed
    {
        try {
            return $this->connection->transact($fn);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }
}
