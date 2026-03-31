<?php

declare(strict_types=1);

namespace Directive\Application\Command;

interface CommandInterface
{
    public function getId(): string;

    public function getCreatedAt(): \DateTimeImmutable;
}
