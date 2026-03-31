<?php

declare(strict_types=1);

namespace Directive\Application\Query;

interface QueryInterface
{
    public function getId(): string;

    public function getCreatedAt(): \DateTimeImmutable;
}
