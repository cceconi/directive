<?php

declare(strict_types=1);

namespace Directive\Input;

use Directive\Input\Constraint\GenericConstraintInterface;
use Directive\Input\Constraint\NoConstraint;

/** Plain string: strip_tags + collapse whitespace. */
final class SimpleString extends InterfaceData
{
    public function __construct(
        GenericConstraintInterface $constraint = new NoConstraint(),
        string $errorLabel = '',
    ) {
        parent::__construct($constraint, $errorLabel);
    }

    protected function clean(mixed $raw): ?string
    {
        if (!is_string($raw) && !is_numeric($raw)) {
            return null;
        }

        return trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $raw)));
    }
}
