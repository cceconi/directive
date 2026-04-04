<?php

declare(strict_types=1);

use Directive\Exception\PersistenceException;
use Directive\Infrastructure\Persistence\KeyValue\AbstractKeyValueRepository;
use Directive\Infrastructure\Persistence\KeyValue\StorageInterface;

// ---------------------------------------------------------------------------
// Concrete test double
// ---------------------------------------------------------------------------

final class ConcreteSessionKeyValueRepository extends AbstractKeyValueRepository
{
    public function findSession(string $token): mixed
    {
        return $this->get('session:' . $token);
    }

    public function saveSession(string $token, mixed $data, ?int $ttl = null): void
    {
        $this->set('session:' . $token, $data, $ttl);
    }

    public function deleteSession(string $token): void
    {
        $this->delete('session:' . $token);
    }

    public function hasSession(string $token): bool
    {
        return $this->exists('session:' . $token);
    }
}

// ---------------------------------------------------------------------------
// MockStorage builder
// ---------------------------------------------------------------------------

function makeStorage(
    mixed $getResult = null,
    bool $existsResult = false,
    ?\Throwable $throws = null,
): StorageInterface {
    return new class($getResult, $existsResult, $throws) implements StorageInterface {
        public function __construct(
            private readonly mixed $getResult,
            private readonly bool $existsResult,
            private readonly ?\Throwable $throws,
        ) {}

        public function get(string $key): mixed
        {
            if ($this->throws !== null) { throw $this->throws; }
            return $this->getResult;
        }

        public function set(string $key, mixed $value, ?int $ttl = null): void
        {
            if ($this->throws !== null) { throw $this->throws; }
        }

        public function delete(string $key): void
        {
            if ($this->throws !== null) { throw $this->throws; }
        }

        public function exists(string $key): bool
        {
            if ($this->throws !== null) { throw $this->throws; }
            return $this->existsResult;
        }
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('AbstractKeyValueRepository', function (): void {

    it('get returns null when key does not exist', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(makeStorage(getResult: null));

        expect($repo->findSession('unknown-token'))->toBeNull();
    });

    it('get returns stored value', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(makeStorage(getResult: ['user_id' => '42']));

        expect($repo->findSession('tok'))->toBe(['user_id' => '42']);
    });

    it('set does not throw when driver succeeds', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(makeStorage());

        expect(fn () => $repo->saveSession('tok', ['user_id' => '42'], 3600))
            ->not->toThrow(\Throwable::class);
    });

    it('delete does not throw when driver succeeds', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(makeStorage());

        expect(fn () => $repo->deleteSession('tok'))->not->toThrow(\Throwable::class);
    });

    it('exists returns false when key is absent', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(makeStorage(existsResult: false));

        expect($repo->hasSession('ghost'))->toBeFalse();
    });

    it('exists returns true when key is present', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(makeStorage(existsResult: true));

        expect($repo->hasSession('active-token'))->toBeTrue();
    });

    it('get wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(
            makeStorage(throws: new \RuntimeException('connection refused'))
        );

        expect(fn () => $repo->findSession('tok'))
            ->toThrow(PersistenceException::class, 'connection refused');
    });

    it('set wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(
            makeStorage(throws: new \RuntimeException('OOM'))
        );

        expect(fn () => $repo->saveSession('tok', 'v'))
            ->toThrow(PersistenceException::class, 'OOM');
    });

    it('delete wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(
            makeStorage(throws: new \RuntimeException('write timeout'))
        );

        expect(fn () => $repo->deleteSession('tok'))
            ->toThrow(PersistenceException::class, 'write timeout');
    });

    it('exists wraps driver exception in PersistenceException', function (): void {
        $repo = new ConcreteSessionKeyValueRepository(
            makeStorage(throws: new \RuntimeException('cluster down'))
        );

        expect(fn () => $repo->hasSession('tok'))
            ->toThrow(PersistenceException::class, 'cluster down');
    });
});
