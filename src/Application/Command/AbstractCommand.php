<?php

declare(strict_types=1);

namespace Directive\Application\Command;

use Ramsey\Uuid\Uuid;

abstract class AbstractCommand implements CommandInterface
{
    private readonly string $id;
    private readonly \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid7()->toString();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
