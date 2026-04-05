<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Injects framework-level context into every log record's `extra` field:
 *   - request_id  : current HTTP request identifier (empty string in CLI context)
 *   - env         : environment code (e.g. "prod", "staging")
 *   - app_version : application version string (e.g. "1.2.3")
 *
 * Registered as a processor on DirectiveLogger so all log channels enrich
 * records automatically without caller-side boilerplate.
 */
final class DirectiveContextProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestIdHolder $holder,
        private readonly LoggingConfigInterface $config,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(extra: array_merge($record->extra, [
            'request_id'  => $this->holder->get()->value,
            'env'         => $this->config->getAppEnv(),
            'app_version' => $this->config->getAppVersion(),
        ]));
    }
}
