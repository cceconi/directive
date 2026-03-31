<?php

declare(strict_types=1);

namespace Directive\Application\EventBus;

use Directive\Application\Event\DomainEventInterface;

final class NullDomainEventBus implements DomainEventBusInterface
{
    public function dispatch(DomainEventInterface $event): void
    {
        // no-op
    }
}
