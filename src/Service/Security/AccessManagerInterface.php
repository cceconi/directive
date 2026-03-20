<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Authenticates the current request and resolves the active WebUser profile.
 * Concrete implementation lives in Epic 7.
 */
interface AccessManagerInterface
{
    public const string COOKIE_TOKEN = 'access_token';

    /**
     * Inspect the request (bearer token, cookie, …) and populate the WebUser.
     * Must not throw — authentication failures result in GUEST profile.
     */
    public function authenticate(ServerRequestInterface $request): void;
}
