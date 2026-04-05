<?php

declare(strict_types=1);

use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\LoggingConfigInterface;
use Directive\Service\Logging\LogStrategy;
use Tests\Helpers\TestConfig;

describe('DefaultLoggingConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['LOG_PATH'],
            $_ENV['LOG_STRATEGY'],
            $_ENV['LOG_BUFFER_SIZE'],
        );
    });

    it('implements LoggingConfigInterface', function (): void {
        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();
        expect($loggingConfig)->toBeInstanceOf(LoggingConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();

        expect($loggingConfig->getLogPath())->toBe('var/log');
        expect($loggingConfig->getLogStrategy())->toBe(LogStrategy::Immediate);
        expect($loggingConfig->getLogBufferSize())->toBe(200);
    });

    it('reads values from $_ENV', function (): void {
        $_ENV['LOG_PATH']      = '/var/log/myapp';
        $_ENV['LOG_STRATEGY']  = 'buffered_on_error';
        $_ENV['LOG_BUFFER_SIZE'] = '50';

        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();

        expect($loggingConfig->getLogPath())->toBe('/var/log/myapp');
        expect($loggingConfig->getLogStrategy())->toBe(LogStrategy::BufferedOnError);
        expect($loggingConfig->getLogBufferSize())->toBe(50);
    });
});
