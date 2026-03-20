<?php

declare(strict_types=1);

namespace Directive\Web\InterfaceData;

use Directive\Web\Constraints\GenericConstraintInterface;
use Directive\Web\Constraints\NoConstraint;

/** Boolean input. Accepts true/false/1/0/"true"/"false"/"1"/"0". */
final class Boolean extends InterfaceData
{
    public function __construct(
        GenericConstraintInterface $constraint = new NoConstraint(),
        string $errorLabel = '',
    ) {
        parent::__construct($constraint, $errorLabel);
    }

    protected function clean(mixed $raw): ?bool
    {
        if (is_bool($raw)) {
            return $raw;
        }

        if ($raw === 1 || $raw === '1' || $raw === 'true') {
            return true;
        }

        if ($raw === 0 || $raw === '0' || $raw === 'false') {
            return false;
        }

        return null;
    }
}
