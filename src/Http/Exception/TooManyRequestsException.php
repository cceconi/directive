<?php

declare(strict_types=1);

namespace Directive\Http\Exception;

use Directive\Exception\DirectiveException;

/**
 * Thrown when a rate limit is exceeded.
 * Maps to HTTP 429 Too Many Requests in Router::resolve().
 */
class TooManyRequestsException extends DirectiveException
{
    public function __construct(
        private readonly int $resetIn = 0,
    ) {
        parent::__construct('Too Many Requests');
    }

    public function getResetIn(): int
    {
        return $this->resetIn;
    }

    public function httpStatus(): int
    {
        return 429;
    }
}
