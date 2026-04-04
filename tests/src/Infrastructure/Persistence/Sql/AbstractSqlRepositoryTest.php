<?php

declare(strict_types=1);

use Directive\Exception\PersistenceException;
use Directive\Infrastructure\Persistence\Sql\AbstractSqlRepository;
use Directive\Infrastructure\Persistence\Sql\ConnectionInterface;

// ---------------------------------------------------------------------------
// Concrete test double
// ---------------------------------------------------------------------------

final class ConcreteUserSqlRepository extends AbstractSqlRepository
{
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function findAll(): array
    {
        return $this->fetchAll('SELECT * FROM users', []);
    }

    public function insert(string $sql, array $params = []): int
    {
        return $this->execute($sql, $params);
    }
}

// ---------------------------------------------------------------------------
// MockConnection builder
// ---------------------------------------------------------------------------

function makeSqlConnection(
    ?array $fetchOneResult = null,
    array $fetchAllResult = [],
    int $executeResult = 0,
    ?\Throwable $throws = null,
): ConnectionInterface {
    return new class($fetchOneResult, $fetchAllResult, $executeResult, $throws) implements ConnectionInterface {
        public function __construct(
            private readonly ?array $fetchOneResult,
            private readonly array $fetchAllResult,
            private readonly int $executeResult,
            private readonly ?\Throwable $throws,
        ) {}

        public function fetchOne(string $sql, array $params = []): ?array
        {
            if ($this->throws !== null) { throw $this->throws; }
            return $this->fetchOneResult;
        }

        public function fetchAll(string $sql, array $params = []): array
        {
            if ($this->throws !== null) { throw $this->throws; }
            return $this->fetchAllResult;
        }

        public function execute(string $sql, array $params = []): int
        {
            if ($this->throws !== null) { throw $this->throws; }
            return $this->executeResult;
        }
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('AbstractSqlRepository', function (): void {

    it('fetchOne returns null when no row found', function (): void {
        $repo = new ConcreteUserSqlRepository(makeSqlConnection(fetchOneResult: null));

        expect($repo->findByEmail('ghost@example.com'))->toBeNull();
    });

    it('fetchOne returns the row when found', function (): void {
        $row  = ['id' => '1', 'email' => 'alice@example.com'];
        $repo = new ConcreteUserSqlRepository(makeSqlConnection(fetchOneResult: $row));

        expect($repo->findByEmail('alice@example.com'))->toBe($row);
    });

    it('fetchAll returns empty array when no rows', function (): void {
        $repo = new ConcreteUserSqlRepository(makeSqlConnection(fetchAllResult: []));

        expect($repo->findAll())->toBe([]);
    });

    it('fetchAll returns all rows', function (): void {
        $rows = [['id' => '1'], ['id' => '2']];
        $repo = new ConcreteUserSqlRepository(makeSqlConnection(fetchAllResult: $rows));

        expect($repo->findAll())->toBe($rows);
    });

    it('execute returns affected row count', function (): void {
        $repo = new ConcreteUserSqlRepository(makeSqlConnection(executeResult: 3));

        expect($repo->insert('INSERT INTO users VALUES (?, ?)', ['a', 'b']))->toBe(3);
    });

    it('fetchOne wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteUserSqlRepository(
            makeSqlConnection(throws: new \RuntimeException('connection lost'))
        );

        expect(fn () => $repo->findByEmail('x@example.com'))
            ->toThrow(PersistenceException::class, 'connection lost');
    });

    it('fetchAll wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteUserSqlRepository(
            makeSqlConnection(throws: new \RuntimeException('timeout'))
        );

        expect(fn () => $repo->findAll())
            ->toThrow(PersistenceException::class, 'timeout');
    });

    it('execute wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteUserSqlRepository(
            makeSqlConnection(throws: new \RuntimeException('deadlock'))
        );

        expect(fn () => $repo->insert('DELETE FROM users WHERE id = ?', ['99']))
            ->toThrow(PersistenceException::class, 'deadlock');
    });

    it('PersistenceException preserves the original exception as previous', function (): void {
        $original = new \RuntimeException('original');
        $repo     = new ConcreteUserSqlRepository(makeSqlConnection(throws: $original));

        try {
            $repo->findAll();
        } catch (PersistenceException $e) {
            expect($e->getPrevious())->toBe($original);
        }
    });
});
