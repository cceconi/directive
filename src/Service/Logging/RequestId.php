<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

/**
 * Immutable value object representing a request identifier.
 * Defaults to an empty string when no request context is available (e.g. CLI).
 */
readonly class RequestId
{
    public function __construct(public string $value = '') {}
}
