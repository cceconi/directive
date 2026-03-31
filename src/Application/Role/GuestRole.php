<?php

declare(strict_types=1);

namespace Directive\Application\Role;

/**
 * Represents an anonymous (unauthenticated) user.
 *
 * Returns Permission::Forbidden for all use-cases by default.
 * Subclass and override getPermission() to allow public routes.
 */
class GuestRole extends AbstractRole
{
    public function getPermission(string $useCase): Permission
    {
        return Permission::Forbidden;
    }

    public function slug(): string
    {
        return 'guest';
    }
}
