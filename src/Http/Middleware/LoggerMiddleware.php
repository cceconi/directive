<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\AppManagement\AppInfoInterface;
use Directive\Service\Logging\WebLoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Logs the application version on entry and flushes the log on exit.
 */
final class LoggerMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var WebLoggerInterface $logger */
        $logger = $this->container->get(WebLoggerInterface::class);

        /** @var AppInfoInterface $appInfo */
        $appInfo = $this->container->get(AppInfoInterface::class);

        $logger->logVersion($appInfo->getVersion());

        $response = $handler->handle($request);

        $logger->write();

        return $response;
    }
}
