<?php

declare(strict_types=1);

namespace Directive\Input;

use Directive\Input\Constraint\GenericConstraintInterface;
use Directive\Input\Constraint\NoConstraint;

/** Username/pseudo: strip tags + collapse whitespace. */
final class Pseudo extends InterfaceData
{
    public function __construct(
        GenericConstraintInterface $constraint = new NoConstraint(),
        string $errorLabel = '',
    ) {
        parent::__construct($constraint, $errorLabel);
    }

    protected function clean(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        return trim((string) preg_replace('/\s+/', ' ', strip_tags($raw)));
    }
}
