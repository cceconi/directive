<?php

declare(strict_types=1);

namespace Directive\Input;

use Directive\Input\Constraint\FormatConstraint;
use Directive\Input\Constraint\GenericConstraintInterface;

/** Email address field: strips tags, lowercases, validates format. */
final class EmailAddress extends InterfaceData
{
    public function __construct(
        ?GenericConstraintInterface $constraint = null,
        string $errorLabel = '',
    ) {
        parent::__construct(
            $constraint ?? new FormatConstraint('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'),
            $errorLabel,
        );
    }

    protected function clean(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $cleaned = strtolower(trim(strip_tags($raw)));

        // Remove characters not valid in email addresses
        return (string) preg_replace('/[^a-zA-Z0-9._%+\-@]/', '', $cleaned);
    }
}
