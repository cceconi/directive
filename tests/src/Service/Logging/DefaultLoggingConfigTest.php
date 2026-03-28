<?php

declare(strict_types=1);

use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\LoggingConfigInterface;

describe('DefaultLoggingConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_LOG_PATH'],
            $_ENV['DIRECTIVE_APP_CODE'],
            $_ENV['DIRECTIVE_ENV_CODE'],
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
    });

    it('reads values from $_ENV', function (): void {
        $_ENV['DIRECTIVE_LOG_PATH'] = '/var/log/myapp';
        $_ENV['DIRECTIVE_APP_CODE'] = 'myapp';
        $_ENV['DIRECTIVE_ENV_CODE'] = 'staging';

        $config = new DefaultLoggingConfig();
        $config->audit();

        expect($config->getLogPath())->toBe('/var/log/myapp');
        expect($config->getAppCode())->toBe('myapp');
        expect($config->getEnvCode())->toBe('staging');
    });
});
