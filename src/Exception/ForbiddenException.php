<?php

declare(strict_types=1);

namespace Directive\Exception;

/** Maps to HTTP 403. Wrong profile or CORS origin. */
class ForbiddenException extends DirectiveException
{
    public function httpStatus(): int
    {
        return 403;
    }
}
