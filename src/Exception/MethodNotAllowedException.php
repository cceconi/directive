<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Thrown when an HTTP method is not registered on a resource.
 * Maps to HTTP 405.
 */
class MethodNotAllowedException extends DirectiveException
{
    public function httpStatus(): int
    {
        return 405;
    }
}
