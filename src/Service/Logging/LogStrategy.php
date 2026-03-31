<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

/**
 * Logging handler strategy.
 *
 * - Immediate: every record is written to stdout immediately (default).
 * - BufferedOnError: records are buffered in memory and flushed only if an
 *   ERROR or above is emitted. Buffer capped by LoggingConfigInterface::getLogBufferSize().
 */
enum LogStrategy: string
{
    case Immediate = 'immediate';
    case BufferedOnError = 'buffered_on_error';
}
