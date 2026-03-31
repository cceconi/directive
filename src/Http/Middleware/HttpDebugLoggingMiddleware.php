<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\Logging\DirectiveLogger;
use Directive\Service\Security\WebUserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Emits DEBUG-level records for every HTTP request/response cycle.
 *
 * Only active when the 'debug_logging' feature flag is enabled
 * (env DIRECTIVE_DEBUG_LOGGING=true). WebApplication adds it to the
 * middleware stack dynamically after checking the flag.
 *
 * Middleware position: between RequestIdMiddleware (outermost) and
 * LoggerMiddleware, so the request_id is already set in RequestIdHolder
 * when this middleware logs.
 *
 * Execution order per cycle:
 *   1. logRequest() — before delegating to the inner stack
 *   2. inner stack runs (including AuthMiddleware)
 *   3. logWebUser()  — resolved from container after auth
 *   4. logResponse() — after inner stack returns
 */
final class HttpDebugLoggingMiddleware extends AbstractMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var DirectiveLogger $logger */
        $logger = $this->container->get(DirectiveLogger::class);

        $logger->logRequest($request);

        $response = $handler->handle($request);

        $user = null;
        if ($this->container->has(WebUserInterface::class)) {
            /** @var WebUserInterface|null $user */
            $user = $this->container->get(WebUserInterface::class);
        }
        $logger->logWebUser($user);
        $logger->logResponse($response);

        return $response;
    }
}
