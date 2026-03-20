<?php

declare(strict_types=1);

namespace Directive\Middleware;

use Directive\Service\Security\CookiesManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reads cookies from the request and appends Set-Cookie headers to the response.
 */
final class CookieMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var CookiesManagerInterface $cookiesManager */
        $cookiesManager = $this->container->get(CookiesManagerInterface::class);

        $cookiesManager->storeFromRequest($request);

        $response = $handler->handle($request);

        return $cookiesManager->setResponseCookies($response);
    }
}
