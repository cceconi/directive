<?php

declare(strict_types=1);

namespace Directive\Web\Constraints;

/** Validates a URI (allows any scheme). */
final class UriConstraint extends GenericConstraint
{
    public function checkType(mixed $value): bool
    {
        return is_string($value);
    }

    public function checkValue(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
