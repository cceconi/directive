<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Thrown when a closed or WIP API version is called.
 * Maps to HTTP 410.
 */
class GoneException extends DirectiveException
{
    public function httpStatus(): int
    {
        return 410;
    }
}
