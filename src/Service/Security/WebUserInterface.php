<?php

declare(strict_types=1);

namespace Directive\Service\Security;

/**
 * Contract for the authenticated (or anonymous) web user.
 * Concrete implementations are application-specific.
 */
interface WebUserInterface
{
    public function getProfile(): string;

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
