<?php

declare(strict_types=1);

use Directive\Application\Event\AbstractDomainEvent;
use Directive\Application\EventBus\DirectiveDomainEventBus;
use Directive\Application\EventBus\DomainEventBusInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

// ---------------------------------------------------------------------------
// Stub concrete event for testing
// ---------------------------------------------------------------------------

final class StubBridgeDomainEvent extends AbstractDomainEvent {}

describe('DirectiveDomainEventBus', function () {
    it('implements DomainEventBusInterface', function () {
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        };

        $bus = new DirectiveDomainEventBus($dispatcher);
        expect($bus)->toBeInstanceOf(DomainEventBusInterface::class);
    });

    it('delegates dispatch() to the PSR-14 EventDispatcherInterface', function () {
        $recorder = new class implements EventDispatcherInterface {
            public ?object $lastReceived = null;
            public function dispatch(object $event): object
            {
                $this->lastReceived = $event;
                return $event;
            }
        };

        $bus   = new DirectiveDomainEventBus($recorder);
        $event = new StubBridgeDomainEvent();
        $bus->dispatch($event);

        expect($recorder->lastReceived)->toBe($event);
    });

    it('forwards the exact event instance to the dispatcher', function () {
        $recorder = new class implements EventDispatcherInterface {
            /** @var list<object> */
            public array $received = [];
            public function dispatch(object $event): object
            {
                $this->received[] = $event;
                return $event;
            }
        };

        $bus    = new DirectiveDomainEventBus($recorder);
        $event1 = new StubBridgeDomainEvent();
        $event2 = new StubBridgeDomainEvent();

        $bus->dispatch($event1);
        $bus->dispatch($event2);

        expect($recorder->received)->toHaveCount(2);
        expect($recorder->received[0])->toBe($event1);
        expect($recorder->received[1])->toBe($event2);
    });
});
