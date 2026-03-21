<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

/**
 * Logger for console commands.
 * Extends WebLoggerInterface so commands reuse the same structured logging contract.
 */
interface ConsoleLoggerInterface extends WebLoggerInterface {}
