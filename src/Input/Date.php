<?php

declare(strict_types=1);

namespace Directive\Input;

use Directive\Input\Constraint\DateConstraint;
use Directive\Input\Constraint\GenericConstraintInterface;

/** Date string input, validated against a configurable format (default Y-m-d). */
final class Date extends InterfaceData
{
    public function __construct(
        string $format = 'Y-m-d',
        ?GenericConstraintInterface $constraint = null,
        string $errorLabel = '',
    ) {
        parent::__construct($constraint ?? new DateConstraint($format), $errorLabel);
    }

    protected function clean(mixed $raw): ?string
    {
        return is_string($raw) ? trim($raw) : null;
    }
}
