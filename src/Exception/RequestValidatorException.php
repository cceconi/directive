<?php

declare(strict_types=1);

namespace Directive\Exception;

/** Thrown when a field is registered twice in a request validator. Programming error. */
class RequestValidatorException extends DirectiveException {}
