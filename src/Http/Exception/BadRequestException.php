<?php

declare(strict_types=1);

namespace Directive\Http\Exception;

/**
 * Maps to HTTP 400.
 * Thrown when Policy finds validation errors — inherits the errors payload from UnprocessableException.
 */
class BadRequestException extends UnprocessableException
{
    public function httpStatus(): int
    {
        return 400;
    }
}
