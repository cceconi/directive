<?php

declare(strict_types=1);

use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\LoggingConfigInterface;
use Directive\Service\Logging\LogStrategy;

describe('DefaultLoggingConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_LOG_PATH'],
            $_ENV['DIRECTIVE_APP_CODE'],
            $_ENV['DIRECTIVE_ENV_CODE'],
            $_ENV['DIRECTIVE_APP_VERSION'],
            $_ENV['DIRECTIVE_LOG_STRATEGY'],
            $_ENV['DIRECTIVE_LOG_BUFFER_SIZE'],
        );
    });

    it('implements LoggingConfigInterface', function (): void {
        $config = new DefaultLoggingConfig();
        $config->audit();
        expect($config)->toBeInstanceOf(LoggingConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $config = new DefaultLoggingConfig();
        $config->audit();

        expect($config->getLogPath())->toBe('/var/log/directive');
        expect($config->getAppCode())->toBe('app');
        expect($config->getEnvCode())->toBe('prod');
        expect($config->getAppVersion())->toBe('0.0.0');
        expect($config->getLogStrategy())->toBe(LogStrategy::Immediate);
        expect($config->getLogBufferSize())->toBe(200);
    });

    it('reads values from $_ENV', function (): void {
        $_ENV['DIRECTIVE_LOG_PATH']      = '/var/log/myapp';
        $_ENV['DIRECTIVE_APP_CODE']      = 'myapp';
        $_ENV['DIRECTIVE_ENV_CODE']      = 'staging';
        $_ENV['DIRECTIVE_APP_VERSION']   = '2.3.4';
        $_ENV['DIRECTIVE_LOG_STRATEGY']  = 'buffered_on_error';
        $_ENV['DIRECTIVE_LOG_BUFFER_SIZE'] = '50';

        $config = new DefaultLoggingConfig();
        $config->audit();

        expect($config->getLogPath())->toBe('/var/log/myapp');
        expect($config->getAppCode())->toBe('myapp');
        expect($config->getEnvCode())->toBe('staging');
        expect($config->getAppVersion())->toBe('2.3.4');
        expect($config->getLogStrategy())->toBe(LogStrategy::BufferedOnError);
        expect($config->getLogBufferSize())->toBe(50);
    });
});
