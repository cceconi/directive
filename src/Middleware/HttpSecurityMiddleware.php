<?php

declare(strict_types=1);

namespace Directive\Middleware;

use Directive\Rest\HttpResponse;
use Directive\Service\Security\HeaderManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds CORS headers and validates the HTTP security header.
 * Calls HeaderManagerInterface to enforce origin/token policies.
 */
final class HttpSecurityMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var HeaderManagerInterface $headerManager */
        $headerManager = $this->container->get(HeaderManagerInterface::class);

        if (!$headerManager->validateSecurityHeader($request)) {
            /** @var HttpResponse $httpResponse */
            $httpResponse = $this->container->get(HttpResponse::class);
            $response     = $httpResponse->preconditionFailed($headerManager->getError());
        } else {
            $response = $handler->handle($request);
            $response = $headerManager->updateSecurityHeader($response);
        }

        // CORS headers are added to all responses (including failures).
        return $headerManager->addCors($request, $response);
    }
}
