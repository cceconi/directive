<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Service\Security\Role\AbstractRole;
use Directive\Service\Security\Role\GuestRole;
use Directive\Service\Security\Role\Permission;
use Directive\Service\Security\WebUserInterface;

/** Configurable web user stub for tests. Defaults to guest. */
final class StubWebUser implements WebUserInterface
{
    private AbstractRole $role;

    public function __construct()
    {
        $this->role = new GuestRole();
    }

    public function withRole(AbstractRole $role): self
    {
        $clone       = clone $this;
        $clone->role = $role;
        return $clone;
    }

    /**
     * Convenience helper: set the role by slug string.
     * The synthetic role grants Allow permission for every use-case.
     */
    public function withProfile(string $slug): self
    {
        $syntheticRole = new class ($slug) extends AbstractRole {
            public function __construct(private readonly string $roleSlug) {}

            public function getPermission(string $useCase): Permission
            {
                return Permission::Allow;
            }

            public function slug(): string
            {
                return $this->roleSlug;
            }
        };

        return $this->withRole($syntheticRole);
    }

    public function getRole(): AbstractRole
    {
        return $this->role;
    }

    public function isAuthenticated(): bool
    {
        return !$this->isGuest();
    }

    public function isGuest(): bool
    {
        return $this->role instanceof GuestRole;
    }

    public function getId(): string
    {
        return 'test-user-id';
    }

    public function getFullName(): string
    {
        return 'Test User';
    }

    public function loadFromClaims(mixed $claims): void {}

    /** @return array<string, mixed> */
    public function getAuthenticatedData(): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function getAnonymousData(): array
    {
        return [];
    }
}