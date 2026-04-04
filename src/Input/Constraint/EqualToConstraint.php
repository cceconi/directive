<?php

declare(strict_types=1);

namespace Directive\Input\Constraint;

final class EqualToConstraint extends GenericConstraint
{
    public function __construct(
        private readonly mixed $reference,
        bool $strictType = false,
    ) {
        parent::__construct($strictType);
    }

    public function checkValue(mixed $value): bool
    {
        return $value === $this->reference;
    }
}
