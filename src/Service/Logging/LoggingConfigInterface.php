<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

interface LoggingConfigInterface
{
    public function getLogPath(): string;
    public function getAppCode(): string;
    public function getEnvCode(): string;
    public function getAppVersion(): string;
    public function getLogStrategy(): LogStrategy;
    public function getLogBufferSize(): int;
}
