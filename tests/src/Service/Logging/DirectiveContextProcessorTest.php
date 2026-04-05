<?php

declare(strict_types=1);

use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\DirectiveContextProcessor;
use Directive\Service\Logging\RequestId;
use Directive\Service\Logging\RequestIdHolder;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\Helpers\TestConfig;

describe('DirectiveContextProcessor', function (): void {

    function makeRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
        );
    }

    it('injects request_id into extra', function (): void {
        $holder = new RequestIdHolder();
        $holder->set(new RequestId('req-abc'));

        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();

        $processor = new DirectiveContextProcessor($holder, $loggingConfig);
        $result    = $processor(makeRecord());

        expect($result->extra['request_id'])->toBe('req-abc');
    });

    it('injects env from config', function (): void {
        $_ENV['APP_ENV'] = 'test';
        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();

        $processor = new DirectiveContextProcessor(new RequestIdHolder(), $loggingConfig);
        $result    = $processor(makeRecord());

        expect($result->extra['env'])->toBe('test');
        unset($_ENV['APP_ENV']);
    });

    it('injects app_version from config', function (): void {
        $_ENV['APP_VERSION'] = '3.1.4';
        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();

        $processor = new DirectiveContextProcessor(new RequestIdHolder(), $loggingConfig);
        $result    = $processor(makeRecord());

        expect($result->extra['app_version'])->toBe('3.1.4');
        unset($_ENV['APP_VERSION']);
    });

    it('preserves existing extra keys', function (): void {
        $holder = new RequestIdHolder();
        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'msg',
            extra: ['existing' => 'value'],
        );

        $processor = new DirectiveContextProcessor($holder, $loggingConfig);
        $result    = $processor($record);

        expect($result->extra['existing'])->toBe('value');
        expect($result->extra)->toHaveKey('request_id');
    });

    it('uses empty string for request_id in console context', function (): void {
        $holder = new RequestIdHolder(); // default = RequestId('')
        $config = new TestConfig();
        $loggingConfig = new DefaultLoggingConfig($config);
        $config->audit();

        $processor = new DirectiveContextProcessor($holder, $loggingConfig);
        $result    = $processor(makeRecord());

        expect($result->extra['request_id'])->toBe('');
    });
});
