<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Thrown when the API tree is built incorrectly (duplicate registration, etc.).
 * This is a programming error — should never reach production.
 */
class ApiDefinitionException extends DirectiveException {}
