<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Service\Security\Profile;
use Directive\Service\Security\WebUserInterface;

/** Guest web user for tests that need an unauthenticated context. */
final class StubWebUser implements WebUserInterface
{
    private string $profile = Profile::GUEST;

    public function withProfile(string $profile): self
    {
        $clone          = clone $this;
        $clone->profile = $profile;
        return $clone;
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    public function isAuthenticated(): bool
    {
        return $this->profile !== Profile::GUEST;
    }

    public function isGuest(): bool
    {
        return $this->profile === Profile::GUEST;
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

    public function getAuthenticatedData(): array
    {
        return [];
    }

    public function getAnonymousData(): array
    {
        return [];
    }
}
