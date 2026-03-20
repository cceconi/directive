<?php

declare(strict_types=1);

namespace Directive\Web\InterfaceData;

use Directive\Web\Constraints\DateConstraint;
use Directive\Web\Constraints\GenericConstraintInterface;

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
