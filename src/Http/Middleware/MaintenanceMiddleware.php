<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Http\Response\HttpResponse;
use Directive\Service\Maintenance\MaintenanceManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Short-circuits the pipeline with 503 when maintenance mode is active.
 */
final class MaintenanceMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var MaintenanceManagerInterface $maintenance */
        $maintenance = $this->container->get(MaintenanceManagerInterface::class);

        if ($maintenance->isActive()) {
            /** @var HttpResponse $httpResponse */
            $httpResponse = $this->container->get(HttpResponse::class);

            $message = sprintf(
                '%s Please retry after %s.',
                $maintenance->getMessage(),
                $maintenance->getPeriod(),
            );

            return $httpResponse->serviceUnavailable('-', '-', $maintenance->getPeriod(), $message);
        }

        return $handler->handle($request);
    }
}
