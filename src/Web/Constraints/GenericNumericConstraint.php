<?php

declare(strict_types=1);

namespace Directive\Web\Constraints;

/** Base for all numeric constraints. */
abstract class GenericNumericConstraint extends GenericConstraint
{
    public function checkType(mixed $value): bool
    {
        return is_int($value) || is_float($value);
    }
}
