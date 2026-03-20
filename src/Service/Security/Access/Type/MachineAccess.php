<?php

declare(strict_types=1);

namespace Directive\Service\Security\Access\Type;

/**
 * Bearer-token authentication (machine-to-machine / API clients).
 */
final class MachineAccess extends AbstractAccess
{
    public function authenticate(): bool
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        if ($authHeader === '') {
            return false;
        }

        // Expected format: "Bearer <token>"
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }

        $token = substr($authHeader, 7);

        if ($token === '') {
            return false;
        }

        return $this->checkToken($token);
    }
}
