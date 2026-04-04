<?php

declare(strict_types=1);

namespace Directive\Input\Constraint;

/** Validates a date string against a given format (default: Y-m-d). */
final class DateConstraint extends GenericConstraint
{
    public function __construct(
        private readonly string $format = 'Y-m-d',
        bool $strictType = false,
    ) {
        parent::__construct($strictType);
    }

    public function checkType(mixed $value): bool
    {
        return is_string($value);
    }

    public function checkValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $d = \DateTimeImmutable::createFromFormat($this->format, $value);

        return $d !== false && $d->format($this->format) === $value;
    }
}
