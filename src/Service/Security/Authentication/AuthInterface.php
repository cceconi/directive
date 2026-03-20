<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

interface AuthInterface
{
    /**
     * Generate a signed JWT.
     *
     * @param array<string, mixed> $claims Additional payload claims.
     * @return array{jwt: string, renewAfter: int}
     */
    public function generateToken(string $subject, array $claims = []): array;

    /**
     * Validate standard claims (issuer, audience, expiry).
     */
    public function validateToken(string $token): bool;

    /**
     * Verify the token signature.
     */
    public function verifyToken(string $token): bool;

    /**
     * Return all non-standard (application-level) claims.
     *
     * @return array<string, mixed>
     */
    public function getTokenClaims(string $token): array;
}
