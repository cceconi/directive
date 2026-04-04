<?php

declare(strict_types=1);

use Directive\Exception\PersistenceException;
use Directive\Infrastructure\Persistence\Document\AbstractDocumentRepository;
use Directive\Infrastructure\Persistence\Document\ClientInterface;

// ---------------------------------------------------------------------------
// Concrete test double
// ---------------------------------------------------------------------------

final class ConcreteProductDocumentRepository extends AbstractDocumentRepository
{
    public function findBySku(string $sku): ?array
    {
        return $this->findOne('products', ['sku' => $sku]);
    }

    public function findByCategory(string $category): array
    {
        return $this->findMany('products', ['category' => $category]);
    }

    public function save(string $sku, array $data): void
    {
        $this->upsert('products', ['sku' => $sku], $data);
    }

    public function deleteBySku(string $sku): void
    {
        $this->remove('products', ['sku' => $sku]);
    }
}

// ---------------------------------------------------------------------------
// MockClient builder
// ---------------------------------------------------------------------------

function makeDocumentClient(
    ?array $findOneResult = null,
    array $findManyResult = [],
    ?\Throwable $throws = null,
): ClientInterface {
    return new class($findOneResult, $findManyResult, $throws) implements ClientInterface {
        public function __construct(
            private readonly ?array $findOneResult,
            private readonly array $findManyResult,
            private readonly ?\Throwable $throws,
        ) {}

        public function findOne(string $collection, array $filter): ?array
        {
            if ($this->throws !== null) { throw $this->throws; }
            return $this->findOneResult;
        }

        public function findMany(string $collection, array $filter, array $options = []): array
        {
            if ($this->throws !== null) { throw $this->throws; }
            return $this->findManyResult;
        }

        public function upsert(string $collection, array $filter, array $data): void
        {
            if ($this->throws !== null) { throw $this->throws; }
        }

        public function remove(string $collection, array $filter): void
        {
            if ($this->throws !== null) { throw $this->throws; }
        }
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('AbstractDocumentRepository', function (): void {

    it('findOne returns null when document not found', function (): void {
        $repo = new ConcreteProductDocumentRepository(makeDocumentClient(findOneResult: null));

        expect($repo->findBySku('UNKNOWN'))->toBeNull();
    });

    it('findOne returns the document when found', function (): void {
        $doc  = ['sku' => 'ABC-123', 'name' => 'Widget'];
        $repo = new ConcreteProductDocumentRepository(makeDocumentClient(findOneResult: $doc));

        expect($repo->findBySku('ABC-123'))->toBe($doc);
    });

    it('findMany returns empty array when no documents', function (): void {
        $repo = new ConcreteProductDocumentRepository(makeDocumentClient(findManyResult: []));

        expect($repo->findByCategory('ghost'))->toBe([]);
    });

    it('findMany returns all matching documents', function (): void {
        $docs = [['sku' => 'A'], ['sku' => 'B']];
        $repo = new ConcreteProductDocumentRepository(makeDocumentClient(findManyResult: $docs));

        expect($repo->findByCategory('tools'))->toBe($docs);
    });

    it('upsert does not throw when driver succeeds', function (): void {
        $repo = new ConcreteProductDocumentRepository(makeDocumentClient());

        expect(fn () => $repo->save('ABC-123', ['name' => 'Widget']))->not->toThrow(\Throwable::class);
    });

    it('remove does not throw when driver succeeds', function (): void {
        $repo = new ConcreteProductDocumentRepository(makeDocumentClient());

        expect(fn () => $repo->deleteBySku('ABC-123'))->not->toThrow(\Throwable::class);
    });

    it('findOne wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteProductDocumentRepository(
            makeDocumentClient(throws: new \RuntimeException('network error'))
        );

        expect(fn () => $repo->findBySku('X'))
            ->toThrow(PersistenceException::class, 'network error');
    });

    it('findMany wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteProductDocumentRepository(
            makeDocumentClient(throws: new \RuntimeException('cursor timeout'))
        );

        expect(fn () => $repo->findByCategory('tools'))
            ->toThrow(PersistenceException::class, 'cursor timeout');
    });

    it('upsert wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteProductDocumentRepository(
            makeDocumentClient(throws: new \RuntimeException('write conflict'))
        );

        expect(fn () => $repo->save('X', []))
            ->toThrow(PersistenceException::class, 'write conflict');
    });

    it('remove wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteProductDocumentRepository(
            makeDocumentClient(throws: new \RuntimeException('delete failed'))
        );

        expect(fn () => $repo->deleteBySku('X'))
            ->toThrow(PersistenceException::class, 'delete failed');
    });
});
