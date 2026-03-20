<?php

declare(strict_types=1);

namespace Directive\Web\InterfaceData;

use Directive\Web\Constraints\GenericConstraintInterface;
use Directive\Web\Constraints\NoConstraint;

/** Numeric input — accepts int or float strings, returns int|float|null. */
final class SimpleNumeric extends InterfaceData
{
    public function __construct(
        GenericConstraintInterface $constraint = new NoConstraint(),
        string $errorLabel = '',
    ) {
        parent::__construct($constraint, $errorLabel);
    }

    protected function clean(mixed $raw): int|float|null
    {
        if (is_int($raw)) {
            return $raw;
        }

        if (is_float($raw)) {
            return $raw;
        }

        if (is_string($raw) && is_numeric($raw)) {
            return str_contains($raw, '.') ? (float) $raw : (int) $raw;
        }

        return null;
    }
}
