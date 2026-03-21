<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

/**
 * Base for all authentication strategies.
 *
 * Delegates token operations to a TokenManagerInterface instance.
 * The $audience is the JWT audience claim used for validation.
 */
abstract class AbstractAuth implements AuthInterface
{
    public function __construct(
        protected readonly TokenManagerInterface $tokenManager,
        protected readonly string $audience,
    ) {}

    // ------------------------------------------------------------------
    // AuthInterface
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $claims */
    public function generateToken(string $subject, array $claims = []): array
    {
        return $this->tokenManager->getToken($this->audience, $subject, $claims);
    }

    public function validateToken(string $token): bool
    {
        return $this->tokenManager->validate($token, $this->audience);
    }

    public function verifyToken(string $token): bool
    {
        return $this->tokenManager->verify($token);
    }

    /** @return array<string, mixed> */
    public function getTokenClaims(string $token): array
    {
        return $this->tokenManager->getFilteredClaims($token);
    }
}
