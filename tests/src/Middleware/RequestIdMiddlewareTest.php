<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Directive\Http\Middleware\RequestIdMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * A minimal PSR-15 handler that captures the processed request for inspection
 * and returns a bare 200 response.
 */
function makeHandler(Psr17Factory $factory, ?ServerRequestInterface &$captured = null): RequestHandlerInterface
{
    return new class ($factory, $captured) implements RequestHandlerInterface {
        public function __construct(
            private readonly Psr17Factory $factory,
            private ?ServerRequestInterface &$captured,
        ) {}

        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $this->captured = $request;
            return $this->factory->createResponse(200);
        }
    };
}

function makeMiddleware(): RequestIdMiddleware
{
    $builder = new ContainerBuilder();
    return new RequestIdMiddleware($builder->build());
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('RequestIdMiddleware', function (): void {

    it('generates a UUID v7 when X-Request-Id header is absent', function (): void {
        $factory    = new Psr17Factory();
        $request    = new ServerRequest('GET', '/');
        $middleware = makeMiddleware();

        $captured = null;
        $middleware->process($request, makeHandler($factory, $captured));

        $requestId = $captured?->getAttribute('request_id');
        expect($requestId)->toBeString()->not->toBeEmpty();

        // UUID v7: version nibble must be 7
        // Format: xxxxxxxx-xxxx-7xxx-xxxx-xxxxxxxxxxxx
        expect($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    it('sets X-Request-Id response header matching the request attribute', function (): void {
        $factory    = new Psr17Factory();
        $request    = new ServerRequest('GET', '/');
        $middleware = makeMiddleware();

        $captured = null;
        $response = $middleware->process($request, makeHandler($factory, $captured));

        $responseHeader = $response->getHeaderLine('X-Request-Id');
        $requestId      = $captured?->getAttribute('request_id');

        expect($responseHeader)->not->toBeEmpty();
        expect($responseHeader)->toBe($requestId);
    });

    it('propagates an existing non-empty X-Request-Id header', function (): void {
        $factory    = new Psr17Factory();
        $existingId = 'my-upstream-request-id-abc123';
        $request    = new ServerRequest('GET', '/')->withHeader('X-Request-Id', $existingId);
        $middleware = makeMiddleware();

        $captured = null;
        $response = $middleware->process($request, makeHandler($factory, $captured));

        expect($captured?->getAttribute('request_id'))->toBe($existingId);
        expect($response->getHeaderLine('X-Request-Id'))->toBe($existingId);
    });

    it('generates a new UUID v7 when X-Request-Id header is empty string', function (): void {
        $factory    = new Psr17Factory();
        $request    = new ServerRequest('GET', '/')->withHeader('X-Request-Id', '');
        $middleware = makeMiddleware();

        $captured = null;
        $response = $middleware->process($request, makeHandler($factory, $captured));

        $requestId = $captured?->getAttribute('request_id');
        expect($requestId)->not->toBeEmpty();
        expect($requestId)->not->toBe('');
        expect($requestId)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
    });

    it('response X-Request-Id matches request attribute for propagated ID', function (): void {
        $factory    = new Psr17Factory();
        $existingId = '018e3b2a-1234-7abc-8def-000000000001';
        $request    = new ServerRequest('GET', '/')->withHeader('X-Request-Id', $existingId);
        $middleware = makeMiddleware();

        $captured = null;
        $response = $middleware->process($request, makeHandler($factory, $captured));

        expect($response->getHeaderLine('X-Request-Id'))->toBe($existingId);
        expect($captured?->getAttribute('request_id'))->toBe($existingId);
    });
});
