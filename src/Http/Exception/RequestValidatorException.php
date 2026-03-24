<?php

declare(strict_types=1);

namespace Directive\Http\Exception;

use Directive\Exception\DirectiveException;

/** Thrown when a field is registered twice in a request validator. Programming error. */
class RequestValidatorException extends DirectiveException {}
