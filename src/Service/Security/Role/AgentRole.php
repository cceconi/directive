<?php

declare(strict_types=1);

namespace Directive\Service\Security\Role;

/**
 * Represents an AI agent or automated caller.
 *
 * Grants Permission::Allow for all use-cases by default.
 * Subclass and return Permission::Complementary for use-cases where
 * the UseCase itself must apply additional scoping (e.g. data isolation).
 */
class AgentRole extends AbstractRole
{
    public function getPermission(string $useCase): Permission
    {
        return Permission::Allow;
    }

    public function slug(): string
    {
        return 'agent';
    }
}
