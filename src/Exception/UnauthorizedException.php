<?php

declare(strict_types=1);

namespace Directive\Exception;

/** Maps to HTTP 401. Guest user accessing a protected route. */
class UnauthorizedException extends DirectiveException
{
    public function httpStatus(): int
    {
        return 401;
    }
}
