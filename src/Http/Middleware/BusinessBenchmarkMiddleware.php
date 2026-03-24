<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\Logging\WebLoggerInterface;
use Directive\Service\Utils\BenchmarkInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Measures total business execution time and logs benchmark points.
 */
final class BusinessBenchmarkMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var BenchmarkInterface $benchmark */
        $benchmark = $this->container->get(BenchmarkInterface::class);

        /** @var WebLoggerInterface $logger */
        $logger = $this->container->get(WebLoggerInterface::class);

        $benchmark->start();

        $response = $handler->handle($request);

        $logger->logBusinessTimeExecution($benchmark->getFullTimeExecution());

        if ($benchmark->havePoints()) {
            $logger->logBusinessBenchmark($benchmark->getPoints());
        }

        return $response;
    }
}
