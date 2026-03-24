<?php

declare(strict_types=1);

namespace Directive\Http\Input\Constraint;

/**
 * Abstract base constraint.
 * Optionally enforces a strict type check before delegating to checkValue().
 */
abstract class GenericConstraint implements GenericConstraintInterface
{
    public function __construct(
        protected readonly bool $strictType = false,
    ) {}

    final public function checkConstraint(mixed $value): bool
    {
        if ($this->strictType && !$this->checkType($value)) {
            return false;
        }

        return $this->checkValue($value);
    }

    public function checkType(mixed $value): bool
    {
        return true;
    }
}
