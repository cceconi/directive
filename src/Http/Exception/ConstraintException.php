<?php

declare(strict_types=1);

namespace Directive\Http\Exception;

use Directive\Exception\DirectiveException;

/** Thrown when a constraint itself is misconfigured (e.g. invalid regex). */
class ConstraintException extends DirectiveException {}
