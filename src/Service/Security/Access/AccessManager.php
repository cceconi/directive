<?php

declare(strict_types=1);

namespace Directive\Service\Security\Access;

use Directive\Service\Security\Access\Type\MachineAccess;
use Directive\Service\Security\Access\Type\NoneAccess;
use Directive\Service\Security\Access\Type\UserAccess;
use Directive\Service\Security\AccessManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Selects the authentication strategy based on the incoming request:
 *   1. cookie "access_token"  → UserAccess  (browser/SPA)
 *   2. Authorization: Bearer  → MachineAccess (API client)
 *   3. Neither               → NoneAccess  (GUEST)
 *
 * Cookie is checked first to prevent Authorization header abuse.
 */
final class AccessManager implements AccessManagerInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
    ) {}

    public function authenticate(ServerRequestInterface $request): void
    {
        $cookies    = $request->getCookieParams();
        $hasCookie  = isset($cookies[AccessManagerInterface::COOKIE_TOKEN])
            && $cookies[AccessManagerInterface::COOKIE_TOKEN] !== '';
        $authHeader = $request->getHeaderLine('Authorization');

        $access = match (true) {
            $hasCookie   => new UserAccess($this->container, $request),
            $authHeader !== '' => new MachineAccess($this->container, $request),
            default      => new NoneAccess($this->container, $request),
        };

        $access->authenticate();
    }
}
