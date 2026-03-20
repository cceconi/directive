<?php

declare(strict_types=1);

namespace Directive\Exception;

/** Thrown when a constraint itself is misconfigured (e.g. invalid regex). */
class ConstraintException extends DirectiveException {}
