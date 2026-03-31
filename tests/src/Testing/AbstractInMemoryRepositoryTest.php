<?php

declare(strict_types=1);

use Directive\Testing\AbstractInMemoryRepository;

// ---------------------------------------------------------------------------
// Concrete test double for AbstractInMemoryRepository
// ---------------------------------------------------------------------------

final class InMemoryUserRepo extends AbstractInMemoryRepository
{
    /**
     * @param object{id: string, name: string} $entity
     */
    protected function getId(mixed $entity): string
    {
        return $entity->id;
    }

    public function save(mixed $entity): void
    {
        parent::save($entity);
    }

    public function findById(string $id): mixed
    {
        return parent::findById($id);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function delete(string $id): void
    {
        parent::delete($id);
    }

    public function count(): int
    {
        return parent::count();
    }

    public function clear(): void
    {
        parent::clear();
    }
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('AbstractInMemoryRepository', function (): void {

    beforeEach(function (): void {
        $this->repo = new InMemoryUserRepo();
    });

    it('save() and findById() roundtrip', function (): void {
        $user = (object) ['id' => 'u1', 'name' => 'Alice'];
        $this->repo->save($user);

        expect($this->repo->findById('u1'))->toBe($user);
    });

    it('findById() returns null when entity does not exist', function (): void {
        expect($this->repo->findById('missing'))->toBeNull();
    });

    it('findAll() returns all saved entities', function (): void {
        $u1 = (object) ['id' => 'u1', 'name' => 'Alice'];
        $u2 = (object) ['id' => 'u2', 'name' => 'Bob'];
        $this->repo->save($u1);
        $this->repo->save($u2);

        expect($this->repo->findAll())->toHaveCount(2);
    });

    it('delete() removes the entity', function (): void {
        $user = (object) ['id' => 'u1', 'name' => 'Alice'];
        $this->repo->save($user);
        $this->repo->delete('u1');

        expect($this->repo->findById('u1'))->toBeNull();
        expect($this->repo->count())->toBe(0);
    });

    it('count() returns number of stored entities', function (): void {
        expect($this->repo->count())->toBe(0);

        $this->repo->save((object) ['id' => 'u1', 'name' => 'Alice']);
        $this->repo->save((object) ['id' => 'u2', 'name' => 'Bob']);

        expect($this->repo->count())->toBe(2);
    });

    it('clear() removes all entities', function (): void {
        $this->repo->save((object) ['id' => 'u1', 'name' => 'Alice']);
        $this->repo->clear();

        expect($this->repo->count())->toBe(0);
        expect($this->repo->findAll())->toBe([]);
    });

    it('save() replaces existing entity with same id', function (): void {
        $v1 = (object) ['id' => 'u1', 'name' => 'Alice'];
        $v2 = (object) ['id' => 'u1', 'name' => 'Alice Updated'];
        $this->repo->save($v1);
        $this->repo->save($v2);

        expect($this->repo->count())->toBe(1);
        expect($this->repo->findById('u1'))->toBe($v2);
    });
});
