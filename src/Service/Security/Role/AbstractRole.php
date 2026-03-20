<?php

declare(strict_types=1);

namespace Directive\Service\Security\Role;

/**
 * Base class for all UCAC roles.
 *
 * Each concrete role encodes its own permissions per use-case.
 * Extend this class in your application to define domain-specific roles.
 *
 * Usage:
 *   class AdminRole extends AbstractRole {
 *       public function getPermission(string $useCase): Permission {
 *           return Permission::Allow;
 *       }
 *   }
 */
abstract class AbstractRole
{
    /**
     * Return the permission this role grants for the given use-case identifier.
     *
     * @param string $useCase A string identifier for the use-case (e.g. 'create-user', 'read-order').
     */
    abstract public function getPermission(string $useCase): Permission;

    /**
     * Returns true if this role allows access to the given use-case.
     *
     * Permission::Allow and Permission::Complementary both return true.
     * Permission::Complementary means the UseCase must perform additional checks.
     */
    public function allows(string $useCase): bool
    {
        return $this->getPermission($useCase) !== Permission::Forbidden;
    }

    /**
     * A stable string identifier for this role, used in JWT claims and allowedRoles checks.
     * Defaults to the FQCN; override to return a short slug (e.g. 'admin', 'guest').
     */
    public function slug(): string
    {
        return static::class;
    }
}