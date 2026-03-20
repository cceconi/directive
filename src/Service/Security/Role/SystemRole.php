<?php

declare(strict_types=1);

namespace Directive\Service\Security\Role;

/**
 * Represents an internal machine/system call (cron, CLI, internal service).
 *
 * Grants Permission::Allow for all use-cases unconditionally.
 */
class SystemRole extends AbstractRole
{
    public function getPermission(string $useCase): Permission
    {
        return Permission::Allow;
    }

    public function slug(): string
    {
        return 'system';
    }
}