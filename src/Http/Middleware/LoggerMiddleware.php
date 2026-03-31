<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\AppManagement\AppInfoInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Logs the application version on every request.
 */
final class LoggerMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);

        /** @var AppInfoInterface $appInfo */
        $appInfo = $this->container->get(AppInfoInterface::class);

        $response = $handler->handle($request);

        $logger->info('request.complete', ['app_version' => $appInfo->getVersion()]);

        return $response;
    }
}
