<?php

declare(strict_types=1);

namespace Directive\Web\Constraints;

final class GreaterThanConstraint extends GenericNumericConstraint
{
    public function __construct(
        private readonly int|float $reference,
        bool $strictType = false,
    ) {
        parent::__construct($strictType);
    }

    public function checkValue(mixed $value): bool
    {
        return (is_int($value) || is_float($value)) && $value > $this->reference;
    }
}
