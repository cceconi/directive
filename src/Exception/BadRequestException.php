<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Maps to HTTP 400.
 * Thrown when Policy finds validation errors — inherits the errors payload from ConflictException.
 */
class BadRequestException extends ConflictException
{
    public function httpStatus(): int
    {
        return 400;
    }
}
