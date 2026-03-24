<?php

declare(strict_types=1);

namespace Directive\Http\Input\Constraint;

/** Value must be in an explicit allowed list (enum-style). */
final class ListValuesConstraint extends GenericConstraint
{
    /** @param array<mixed> $allowedValues */
    public function __construct(
        private readonly array $allowedValues,
        bool $strictType = false,
    ) {
        parent::__construct($strictType);
    }

    public function checkValue(mixed $value): bool
    {
        return in_array($value, $this->allowedValues, strict: true);
    }
}
