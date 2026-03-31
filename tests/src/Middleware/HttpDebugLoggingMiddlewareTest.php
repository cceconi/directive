<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Directive\Http\Middleware\HttpDebugLoggingMiddleware;
use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\DirectiveLogger;
use Directive\Service\Logging\RequestIdHolder;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeDebugLogger(): array
{
    $config = new DefaultLoggingConfig();
    $config->audit();

    $logger      = new DirectiveLogger($config, new RequestIdHolder());
    $testHandler = new TestHandler(Level::Debug, bubble: false);
    $logger->setHandlers([$testHandler]);

    return [$logger, $testHandler];
}

function makeHMHandler(Psr17Factory $factory, int $status = 200): RequestHandlerInterface
{
    return new class ($factory, $status) implements RequestHandlerInterface {
        public function __construct(
            private readonly Psr17Factory $factory,
            private readonly int $status,
        ) {}

        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return $this->factory->createResponse($this->status);
        }
    };
}

function makeDebugMiddleware(DirectiveLogger $logger): HttpDebugLoggingMiddleware
{
    $builder = new ContainerBuilder();
    $builder->addDefinitions([DirectiveLogger::class => $logger]);
    return new HttpDebugLoggingMiddleware($builder->build());
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('HttpDebugLoggingMiddleware', function (): void {

    it('logs http.request before delegating to handler', function (): void {
        [$logger, $handler] = makeDebugLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $factory    = new Psr17Factory();
        $request    = new ServerRequest('POST', 'https://example.com/api/v1/foo');
        $middleware = makeDebugMiddleware($logger);

        $middleware->process($request, makeHMHandler($factory));

        $messages = array_column($handler->getRecords(), 'message');
        expect($messages)->toContain('http.request');
    });

    it('logs http.response after the handler returns', function (): void {
        [$logger, $handler] = makeDebugLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $factory    = new Psr17Factory();
        $request    = new ServerRequest('GET', 'https://example.com/api/v1/bar');
        $middleware = makeDebugMiddleware($logger);

        $middleware->process($request, makeHMHandler($factory, 204));

        $messages = array_column($handler->getRecords(), 'message');
        expect($messages)->toContain('http.response');

        $record = $handler->getRecords()[array_search('http.response', $messages, true)];
        expect($record['context']['status'])->toBe(204);
    });

    it('passes the response transparently', function (): void {
        [$logger] = makeDebugLogger();
        /** @var DirectiveLogger $logger */

        $factory    = new Psr17Factory();
        $request    = new ServerRequest('GET', '/');
        $middleware = makeDebugMiddleware($logger);

        $response = $middleware->process($request, makeHMHandler($factory, 418));

        expect($response->getStatusCode())->toBe(418);
    });

    it('records method and uri in http.request context', function (): void {
        [$logger, $handler] = makeDebugLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $factory    = new Psr17Factory();
        $request    = new ServerRequest('DELETE', 'https://example.com/api/v1/resource/42');
        $middleware = makeDebugMiddleware($logger);

        $middleware->process($request, makeHMHandler($factory));

        $record = current(array_filter(
            $handler->getRecords(),
            fn ($r) => $r['message'] === 'http.request',
        ));

        expect($record['context']['method'])->toBe('DELETE');
        expect($record['context']['uri'])->toContain('/api/v1/resource/42');
    });
});
