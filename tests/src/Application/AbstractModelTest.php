<?php

declare(strict_types=1);

use Directive\Application\Model\AbstractEntity;
use Directive\Application\Model\AbstractUid;
use Directive\Application\Model\Type\AggregateRootInterface;
use Directive\Application\Model\Type\EntityInterface;
use Directive\Application\Model\Type\ValueObjectInterface;

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

class ModelTestUid extends AbstractUid {}

class ConcreteEntity extends AbstractEntity
{
    public function __construct(private readonly ModelTestUid $uid) {}

    public function getId(): AbstractUid
    {
        return $this->uid;
    }
}

class ConcreteAggregate extends AbstractEntity implements AggregateRootInterface
{
    public function __construct(private readonly ModelTestUid $uid) {}

    public function getId(): AbstractUid
    {
        return $this->uid;
    }
}

class EmailValueObject implements ValueObjectInterface
{
    public function __construct(public readonly string $value) {}
}

// ---------------------------------------------------------------------------
// AbstractUid (S3 — getId contract)
// ---------------------------------------------------------------------------

describe('AbstractEntity', function () {
    it('getId() returns the AbstractUid passed at construction', function () {
        $uid = new ModelTestUid();
        $entity = new ConcreteEntity($uid);
        expect($entity->getId())->toBe($uid);
    });

    it('getId() returns an AbstractUid instance', function () {
        $entity = new ConcreteEntity(new ModelTestUid());
        expect($entity->getId())->toBeInstanceOf(AbstractUid::class);
    });

    it('two entities with distinct UIDs have different IDs', function () {
        $e1 = new ConcreteEntity(new ModelTestUid());
        $e2 = new ConcreteEntity(new ModelTestUid());
        expect($e1->getId()->getValue())->not->toBe($e2->getId()->getValue());
    });
});

// ---------------------------------------------------------------------------
// DDD marker interfaces
// ---------------------------------------------------------------------------

describe('AggregateRootInterface', function () {
    it('extends EntityInterface', function () {
        $agg = new ConcreteAggregate(new ModelTestUid());
        expect($agg)->toBeInstanceOf(EntityInterface::class);
        expect($agg)->toBeInstanceOf(AggregateRootInterface::class);
    });
});

describe('ValueObjectInterface', function () {
    it('is implementable as a readonly value class', function () {
        $email = new EmailValueObject('user@example.com');
        expect($email)->toBeInstanceOf(ValueObjectInterface::class);
    });
});
