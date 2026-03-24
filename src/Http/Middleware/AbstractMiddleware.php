<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Base PSR-15 middleware.
 *
 * Subclasses receive the DI container for lazy service resolution.
 */
abstract class AbstractMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected readonly ContainerInterface $container,
    ) {}

    abstract public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface;
}
