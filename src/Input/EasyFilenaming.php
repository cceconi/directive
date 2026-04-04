<?php

declare(strict_types=1);

namespace Directive\Input;

use Directive\Input\Constraint\GenericConstraintInterface;
use Directive\Input\Constraint\NoConstraint;

/** Filename-safe string: only [A-Za-z0-9._-] allowed. */
final class EasyFilenaming extends InterfaceData
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

        return (string) preg_replace('/[^A-Za-z0-9._\-]/', '', $raw);
    }
}
