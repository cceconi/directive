<?php

declare(strict_types=1);

namespace Directive\Input\Constraint;

interface GenericConstraintInterface
{
    public function checkConstraint(mixed $value): bool;
    public function checkType(mixed $value): bool;
    public function checkValue(mixed $value): bool;
}
