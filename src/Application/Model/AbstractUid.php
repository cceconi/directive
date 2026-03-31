<?php

declare(strict_types=1);

namespace Directive\Application\Model;

use Ramsey\Uuid\Uuid;

abstract class AbstractUid
{
    public readonly string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ?? Uuid::uuid7()->toString();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(AbstractUid $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
