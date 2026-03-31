<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

/**
 * Mutable singleton holding the current request identifier.
 *
 * Bound as a singleton in the DI container so that RequestIdMiddleware and
 * DirectiveContextProcessor share the same instance.
 *
 * Default value is RequestId('') — safe for console context where no HTTP
 * request identifier exists.
 */
final class RequestIdHolder
{
    private RequestId $current;

    public function __construct()
    {
        $this->current = new RequestId();
    }

    public function set(RequestId $requestId): void
    {
        $this->current = $requestId;
    }

    public function get(): RequestId
    {
        return $this->current;
    }
}
