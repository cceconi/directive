<?php

declare(strict_types=1);

namespace Directive\Input\Constraint;

final class BoundedByConstraint extends GenericNumericConstraint
{
    public function __construct(
        private readonly int|float $min,
        private readonly int|float $max,
        bool $strictType = false,
    ) {
        parent::__construct($strictType);
    }

    public function checkValue(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && $value >= $this->min && $value <= $this->max;
    }
}
