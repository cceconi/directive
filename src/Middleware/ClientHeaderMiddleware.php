<?php

declare(strict_types=1);

namespace Directive\Middleware;

use Directive\Service\AppManagement\ClientHeadersInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Parses and stores custom client identification headers.
 */
final class ClientHeaderMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var ClientHeadersInterface $clientHeaders */
        $clientHeaders = $this->container->get(ClientHeadersInterface::class);
        $clientHeaders->load($request);

        return $handler->handle($request);
    }
}
