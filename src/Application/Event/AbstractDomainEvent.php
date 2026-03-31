<?php

declare(strict_types=1);

namespace Directive\Application\Event;

use Ramsey\Uuid\Uuid;

abstract class AbstractDomainEvent implements DomainEventInterface
{
    private readonly string $id;
    private readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->id = Uuid::uuid7()->toString();
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
