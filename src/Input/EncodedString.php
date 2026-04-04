<?php

declare(strict_types=1);

namespace Directive\Input;

use Directive\Input\Constraint\GenericConstraintInterface;
use Directive\Input\Constraint\NoConstraint;

/** Base64-encoded string: validates and decodes on clean. */
final class EncodedString extends InterfaceData
{
    public function __construct(
        GenericConstraintInterface $constraint = new NoConstraint(),
        string $errorLabel = '',
    ) {
        parent::__construct($constraint, $errorLabel);
    }

    protected function clean(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $decoded = base64_decode($raw, strict: true);

        return $decoded !== false ? $decoded : null;
    }
}
