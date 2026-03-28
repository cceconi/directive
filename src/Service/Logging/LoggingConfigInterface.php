<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

interface LoggingConfigInterface
{
    public function getLogPath(): string;
    public function getAppCode(): string;
    public function getEnvCode(): string;
}
