<?php

declare(strict_types=1);

namespace Directive\Web\Constraints;

/** Always passes — use when no validation is needed. */
final class NoConstraint extends GenericConstraint
{
    public function checkValue(mixed $value): bool
    {
        return true;
    }
}
