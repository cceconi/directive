<?php

declare(strict_types=1);

namespace Directive\Exception;

/** Thrown when a field is registered twice in a Policy. Programming error. */
class PolicyException extends DirectiveException {}
