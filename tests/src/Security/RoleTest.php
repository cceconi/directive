<?php

declare(strict_types=1);

use Directive\Application\Role\AbstractRole;
use Directive\Application\Role\AgentRole;
use Directive\Application\Role\GuestRole;
use Directive\Application\Role\Permission;
use Directive\Application\Role\SystemRole;
use Directive\Application\User\DomainUser;

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
    it('returns slug "guest"', function () {
        expect(new GuestRole()->slug())->toBe('guest');
    });
});

// ---------------------------------------------------------------------------
// SystemRole
// ---------------------------------------------------------------------------

describe('SystemRole', function () {
    it('returns slug "system"', function () {
        expect(new SystemRole()->slug())->toBe('system');
    });
});

// ---------------------------------------------------------------------------
// AgentRole
// ---------------------------------------------------------------------------

describe('AgentRole', function () {
    it('returns slug "agent"', function () {
        expect(new AgentRole()->slug())->toBe('agent');
    });
});

// ---------------------------------------------------------------------------
// AbstractRole
// ---------------------------------------------------------------------------

describe('AbstractRole', function () {
    it('slug() defaults to a non-empty string when not overridden', function () {
        $role = new class extends AbstractRole {};
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

});
