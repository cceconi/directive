<?php

declare(strict_types=1);

use Directive\Application\Event\AbstractDomainEvent;
use Directive\Application\Event\DomainEventInterface;
use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\EventBus\NullDomainEventBus;

// ---------------------------------------------------------------------------
// Concrete stub
// ---------------------------------------------------------------------------

class StubDomainEvent extends AbstractDomainEvent {}

// ---------------------------------------------------------------------------
// AbstractDomainEvent
// ---------------------------------------------------------------------------

describe('AbstractDomainEvent', function () {
    it('implements DomainEventInterface', function () {
        $event = new StubDomainEvent();
        expect($event)->toBeInstanceOf(DomainEventInterface::class);
    });

    it('generates a non-empty UUID id', function () {
        $event = new StubDomainEvent();
        expect($event->getId())->not->toBeEmpty();
        expect(strlen($event->getId()))->toBe(36);
    });

    it('provides an occurredAt DateTimeImmutable', function () {
        $event = new StubDomainEvent();
        expect($event->getOccurredAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('two events have distinct IDs', function () {
        $e1 = new StubDomainEvent();
        $e2 = new StubDomainEvent();
        expect($e1->getId())->not->toBe($e2->getId());
    });
});

// ---------------------------------------------------------------------------
// NullDomainEventBus
// ---------------------------------------------------------------------------

describe('NullDomainEventBus', function () {
    it('implements DomainEventBusInterface', function () {
        $bus = new NullDomainEventBus();
        expect($bus)->toBeInstanceOf(DomainEventBusInterface::class);
    });

    it('dispatch() does not throw', function () {
        $bus = new NullDomainEventBus();
        $bus->dispatch(new StubDomainEvent());
        expect(true)->toBeTrue();
    });
});
