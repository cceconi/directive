<?php

declare(strict_types=1);

namespace Directive\Application\Event;

interface DomainEventInterface
{
    public function getId(): string;

    public function getOccurredAt(): \DateTimeImmutable;
}
