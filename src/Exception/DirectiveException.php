<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Root framework exception.
 * All Directive exceptions extend this class.
 *
 * Every subclass that maps to an HTTP status overrides httpStatus().
 * The default is 500 so generic catch blocks can always produce a valid response.
 */
class DirectiveException extends \RuntimeException
{
    /**
     * The HTTP status code this exception maps to.
     * Override in concrete subclasses.
     */
    public function httpStatus(): int
    {
        return 500;
    }
}
