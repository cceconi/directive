<?php

declare(strict_types=1);

use Directive\Application\Role\GuestRole;
use Directive\Application\User\DomainUser;
use Directive\Http\Endpoint\DomainUserFactory;
use Tests\Helpers\StubWebUser;

describe('DomainUserFactory::fromWebUser()', function () {
    it('maps WebUserInterface::getId() to DomainUser::$id', function () {
        $webUser = new StubWebUser();

        $domainUser = DomainUserFactory::fromWebUser($webUser);

        expect($domainUser)->toBeInstanceOf(DomainUser::class);
        expect($domainUser->id)->toBe($webUser->getId());
    });

    it('maps WebUserInterface::getRole() to DomainUser::$role', function () {
        $webUser = new StubWebUser();

        $domainUser = DomainUserFactory::fromWebUser($webUser);

        expect($domainUser->role)->toBeInstanceOf($webUser->getRole()::class);
    });

    it('preserves the exact role instance', function () {
        $role    = new GuestRole();
        $webUser = (new StubWebUser())->withRole($role);

        $domainUser = DomainUserFactory::fromWebUser($webUser);

        expect($domainUser->role)->toBe($role);
    });
});
