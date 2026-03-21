<?php

declare(strict_types=1);

namespace Directive\Service\Security\Access\Type;

use Directive\Service\Security\AccessManagerInterface;

/**
 * Cookie-based authentication (browser / SPA users).
 */
final class UserAccess extends AbstractAccess
{
    public function authenticate(): bool
    {
        $cookies = $this->request->getCookieParams();
        $token   = $cookies[AccessManagerInterface::COOKIE_TOKEN] ?? '';

        if ($token === '') {
            return false;
        }

        return $this->checkToken($token);
    }
}
