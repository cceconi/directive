<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Thrown on web-user related errors (profile mismatch, locked account, etc.).
 * Maps to HTTP 403 by default; callers may catch and remap as needed.
 */
class WebUserException extends DirectiveException
{
    public function httpStatus(): int
    {
        return 403;
    }
}
