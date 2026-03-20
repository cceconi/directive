<?php

declare(strict_types=1);

namespace Directive\Web\InterfaceData;

use Directive\Web\Constraints\GenericConstraintInterface;
use Directive\Web\Constraints\NoConstraint;

/**
 * Password field.
 * Never logged — the logger must check InterfaceData type or property name.
 */
final class Password extends InterfaceData
{
    public function __construct(
        GenericConstraintInterface $constraint = new NoConstraint(),
        string $errorLabel = '',
    ) {
        parent::__construct($constraint, $errorLabel);
    }

    protected function clean(mixed $raw): ?string
    {
        // Passwords are not sanitized — return as-is after basic type check.
        return is_string($raw) ? $raw : null;
    }
}
