<?php

declare(strict_types=1);

namespace Directive\Web\Constraints;

/** Validates an HTTP/HTTPS URL. */
final class UrlConstraint extends GenericConstraint
{
    public function checkType(mixed $value): bool
    {
        return is_string($value);
    }

    public function checkValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_URL);

        if ($filtered === false) {
            return false;
        }

        $scheme = parse_url($filtered, PHP_URL_SCHEME);

        return $scheme === 'http' || $scheme === 'https';
    }
}
