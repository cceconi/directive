<?php

declare(strict_types=1);

namespace Directive\Application\EventBus;

use Directive\Application\Event\DomainEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Adapts PSR-14 EventDispatcherInterface to DomainEventBusInterface.
 *
 * Bind this class as DomainEventBusInterface in the DI container when a
 * PSR-14 dispatcher is available. AbstractApplication does so automatically
 * if EventDispatcherInterface is resolvable and not overridden by the user.
 */
final class DirectiveDomainEventBus implements DomainEventBusInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function dispatch(DomainEventInterface $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
