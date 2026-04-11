<?php

declare(strict_types=1);

use Directive\Service\Logging\DirectiveLogger;
use Directive\Service\Logging\RequestIdHolder;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\StreamHandler;

describe('DirectiveLogger — strategy', function (): void {

    afterEach(function (): void {
        unset($_ENV['LOG_STRATEGY'], $_ENV['LOG_BUFFER_SIZE']);
    });

    it('uses StreamHandler with Immediate strategy', function (): void {
        $_ENV['LOG_STRATEGY'] = 'immediate';
        $logger   = new DirectiveLogger(makeTestLoggingConfig(), new RequestIdHolder());
        $handlers = $logger->getHandlers();

        expect($handlers)->toHaveCount(1);
        expect($handlers[0])->toBeInstanceOf(StreamHandler::class);
    });

    it('uses FingersCrossedHandler with BufferedOnError strategy', function (): void {
        $_ENV['LOG_STRATEGY'] = 'buffered_on_error';
        $logger   = new DirectiveLogger(makeTestLoggingConfig(), new RequestIdHolder());
        $handlers = $logger->getHandlers();

        expect($handlers)->toHaveCount(1);
        expect($handlers[0])->toBeInstanceOf(FingersCrossedHandler::class);
    });

    it('configures FingersCrossedHandler with custom buffer size', function (): void {
        $_ENV['LOG_STRATEGY']    = 'buffered_on_error';
        $_ENV['LOG_BUFFER_SIZE'] = '50';

        $logger  = new DirectiveLogger(makeTestLoggingConfig(), new RequestIdHolder());
        $handler = $logger->getHandlers()[0];

        expect($handler)->toBeInstanceOf(FingersCrossedHandler::class);

        /** @var FingersCrossedHandler $handler */
        $ref     = new ReflectionProperty(FingersCrossedHandler::class, 'bufferSize');
        $bufSize = $ref->getValue($handler);
        expect($bufSize)->toBe(50);
    });

    it('registers DirectiveContextProcessor', function (): void {
        $logger     = new DirectiveLogger(makeTestLoggingConfig(), new RequestIdHolder());
        $processors = $logger->getProcessors();

        expect($processors)->toHaveCount(1);
        expect($processors[0])->toBeInstanceOf(\Directive\Service\Logging\DirectiveContextProcessor::class);
    });

    it('uses app code as logger name', function (): void {
        $logger = new DirectiveLogger(makeTestLoggingConfig(['name' => 'myapp']), new RequestIdHolder());
        expect($logger->getName())->toBe('myapp');
    });
});
