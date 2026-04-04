<?php

declare(strict_types=1);

namespace Directive\Input\Constraint;

use Directive\Exception\ConstraintException;

/** Validates that a string matches a regex pattern. */
final class FormatConstraint extends GenericConstraint
{
    public function __construct(
        private readonly string $pattern,
        bool $strictType = false,
    ) {
        parent::__construct($strictType);

        // Temporarily install a no-op error handler so that an invalid PCRE pattern never
        // emits a PHP warning that test frameworks (e.g. Pest) would capture.
        set_error_handler(static fn(): bool => true);
        $result = preg_match($this->pattern, '');
        restore_error_handler();

        if ($result === false) {
            throw new ConstraintException(sprintf('Invalid regex pattern: %s', $this->pattern));
        }
    }

    public function checkType(mixed $value): bool
    {
        return is_string($value);
    }

    public function checkValue(mixed $value): bool
    {
        return is_string($value) && preg_match($this->pattern, $value) === 1;
    }
}
