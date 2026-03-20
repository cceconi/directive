<?php

declare(strict_types=1);

namespace Directive\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Generates or propagates a unique request identifier.
 *
 * Execution order: 1st (outermost) in the default middleware stack.
 *
 * Behaviour:
 * - If the incoming request carries a non-empty X-Request-Id header, that value
 *   is reused (upstream gateway / proxy propagation).
 * - If the header is absent or empty, a UUID v7 (time-ordered) is generated.
 *
 * The resolved ID is stored as the PSR-7 request attribute 'request_id' for
 * downstream middlewares and handlers, and written as X-Request-Id on the response.
 */
final class RequestIdMiddleware extends AbstractMiddleware
{
    private const HEADER = 'X-Request-Id';
    private const ATTRIBUTE = 'request_id';

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $incoming = $request->getHeaderLine(self::HEADER);
        $requestId = ($incoming !== '') ? $incoming : Uuid::uuid7()->toString();

        $request = $request->withAttribute(self::ATTRIBUTE, $requestId);

        $response = $handler->handle($request);

        return $response->withHeader(self::HEADER, $requestId);
    }
}
