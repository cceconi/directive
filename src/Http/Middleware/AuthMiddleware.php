<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\Security\AccessManagerInterface;
use Directive\Service\Security\CookiesManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authenticates the user and writes updated auth cookies to the response.
 *
 * Must run AFTER CookieMiddleware (cookies must already be loaded).
 */
final class AuthMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var AccessManagerInterface $accessManager */
        $accessManager = $this->container->get(AccessManagerInterface::class);

        /** @var CookiesManagerInterface $cookiesManager */
        $cookiesManager = $this->container->get(CookiesManagerInterface::class);

        $accessManager->authenticate($request);

        $response = $handler->handle($request);

        return $cookiesManager->setResponseCookies($response);
    }
}
