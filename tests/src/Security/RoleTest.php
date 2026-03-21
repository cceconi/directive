<?php

declare(strict_types=1);

use Directive\Service\Security\Role\AbstractRole;
use Directive\Service\Security\Role\AgentRole;
use Directive\Service\Security\Role\DomainUser;
use Directive\Service\Security\Role\GuestRole;
use Directive\Service\Security\Role\Permission;
use Directive\Service\Security\Role\SystemRole;

// ---------------------------------------------------------------------------
// Permission enum
// ---------------------------------------------------------------------------

describe('Permission', function () {
    it('has Allow, Complementary and Forbidden cases', function () {
        expect(Permission::Allow->value)->toBe('allow');
        expect(Permission::Complementary->value)->toBe('complementary');
        expect(Permission::Forbidden->value)->toBe('forbidden');
    });

    it('can be created from backing value', function () {
        expect(Permission::from('allow'))->toBe(Permission::Allow);
        expect(Permission::from('forbidden'))->toBe(Permission::Forbidden);
    });
});

// ---------------------------------------------------------------------------
// GuestRole
// ---------------------------------------------------------------------------

describe('GuestRole', function () {
    it('returns Forbidden for any use-case', function () {
        $role = new GuestRole();
        expect($role->getPermission('create-user'))->toBe(Permission::Forbidden);
        expect($role->getPermission('read-order'))->toBe(Permission::Forbidden);
    });

    it('allows() returns false for any use-case', function () {
        $role = new GuestRole();
        expect($role->allows('anything'))->toBeFalse();
    });

    it('returns slug "guest"', function () {
        expect(new GuestRole()->slug())->toBe('guest');
    });
});

// ---------------------------------------------------------------------------
// SystemRole
// ---------------------------------------------------------------------------

describe('SystemRole', function () {
    it('returns Allow for any use-case', function () {
        $role = new SystemRole();
        expect($role->getPermission('create-user'))->toBe(Permission::Allow);
        expect($role->getPermission('delete-everything'))->toBe(Permission::Allow);
    });

    it('allows() returns true for any use-case', function () {
        $role = new SystemRole();
        expect($role->allows('anything'))->toBeTrue();
    });

    it('returns slug "system"', function () {
        expect(new SystemRole()->slug())->toBe('system');
    });
});

// ---------------------------------------------------------------------------
// AgentRole
// ---------------------------------------------------------------------------

describe('AgentRole', function () {
    it('returns Allow for any use-case', function () {
        $role = new AgentRole();
        expect($role->getPermission('run-task'))->toBe(Permission::Allow);
    });

    it('allows() returns true for any use-case', function () {
        $role = new AgentRole();
        expect($role->allows('anything'))->toBeTrue();
    });

    it('returns slug "agent"', function () {
        expect(new AgentRole()->slug())->toBe('agent');
    });
});

// ---------------------------------------------------------------------------
// AbstractRole — allows() logic
// ---------------------------------------------------------------------------

describe('AbstractRole allows()', function () {
    it('returns true for Allow', function () {
        $role = new class extends AbstractRole {
            public function getPermission(string $useCase): Permission
            {
                return Permission::Allow;
            }
        };
        expect($role->allows('any'))->toBeTrue();
    });

    it('returns true for Complementary', function () {
        $role = new class extends AbstractRole {
            public function getPermission(string $useCase): Permission
            {
                return Permission::Complementary;
            }
        };
        expect($role->allows('any'))->toBeTrue();
    });

    it('returns false for Forbidden', function () {
        $role = new class extends AbstractRole {
            public function getPermission(string $useCase): Permission
            {
                return Permission::Forbidden;
            }
        };
        expect($role->allows('any'))->toBeFalse();
    });

    it('slug() defaults to FQCN when not overridden', function () {
        $role = new class extends AbstractRole {
            public function getPermission(string $useCase): Permission
            {
                return Permission::Allow;
            }
        };
        expect($role->slug())->toBeString()->not->toBeEmpty();
    });
});

// ---------------------------------------------------------------------------
// DomainUser
// ---------------------------------------------------------------------------

describe('DomainUser', function () {
    it('stores id and role', function () {
        $role = new SystemRole();
        $user = new DomainUser('user-123', $role);

        expect($user->id)->toBe('user-123');
        expect($user->role)->toBe($role);
    });

    it('is immutable (readonly)', function () {
        $user = new DomainUser('u1', new GuestRole());

        expect(fn() => $user->id = 'changed') // @phpstan-ignore-line
            ->toThrow(\Error::class);
    });

    it('role can be asked about permissions', function () {
        $user = new DomainUser('u1', new SystemRole());
        expect($user->role->allows('any-use-case'))->toBeTrue();
    });
});
