<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Directive\Service\Security\Role\AbstractRole;

/**
 * Contract for the authenticated (or anonymous) web user.
 * Concrete implementations are application-specific.
 */
interface WebUserInterface
{
    /**
     * Return the UCAC role for this user.
     * Must never return null. Unauthenticated users return GuestRole.
     */
    public function getRole(): AbstractRole;

    public function isAuthenticated(): bool;

    public function isGuest(): bool;

    public function getId(): string;

    public function getFullName(): string;

    /** Load user state from JWT application claims. */
    public function loadFromClaims(mixed $claims): void;

    /**
     * @return array<string, mixed>
     */
    public function getAuthenticatedData(): array;

    /**
     * @return array<string, mixed>
     */
    public function getAnonymousData(): array;
}