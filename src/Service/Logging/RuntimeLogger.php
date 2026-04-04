<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Monolog\ErrorHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Registry;

/**
 * Bootstrapped before any config is loaded.
 * Registers a Monolog ErrorHandler that captures all uncaught PHP errors/exceptions
 * and writes them to a rotating runtime log file.
 *
 * Uses Monolog Registry to avoid double-registration if instantiated more than once.
 */
final class RuntimeLogger
{
    private const string REGISTRY_KEY = 'directive.runtime';

    public function __construct(string $name, string $logDir = '')
    {
        if (Registry::hasLogger(self::REGISTRY_KEY)) {
            return;
        }

        $dir    = $logDir !== '' ? $logDir : sys_get_temp_dir();
        $logger = new Logger($name);
        $logger->pushHandler(new RotatingFileHandler($dir . '/directive_runtime.log', 30));

        ErrorHandler::register($logger);
        Registry::addLogger($logger, self::REGISTRY_KEY);
    }
}
