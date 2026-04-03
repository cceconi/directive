<?php

declare(strict_types=1);

namespace Directive\Application\Role;

/**
 * Base class for all UCAC roles.
 *
 * Roles carry identity and a slug (used in JWT claims and allowedRoles checks).
 * Permissions are declared on each UseCase via getPermissions() — not on the role.
 */
abstract class AbstractRole
{
    /**
     * A stable string identifier for this role, used in JWT claims and allowedRoles checks.
     * Defaults to the FQCN; override to return a short slug (e.g. 'admin', 'guest').
     */
    public function slug(): string
    {
        return static::class;
    }
}
