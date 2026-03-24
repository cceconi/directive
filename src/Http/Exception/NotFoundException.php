<?php

declare(strict_types=1);

namespace Directive\Http\Exception;

use Directive\Exception\DirectiveException;

/**
 * Thrown when a domain/version/service/resource is not found in the API tree.
 * Maps to HTTP 404.
 */
class NotFoundException extends DirectiveException
{
    public function httpStatus(): int
    {
        return 404;
    }
}
