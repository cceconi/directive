<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

/**
 * Contract for JWT generation, validation and claim extraction.
 *
 * Implementations delegate to lcobucci/jwt v5 via a pre-built Configuration.
 */
interface TokenManagerInterface
{
    /**
     * Build and sign a JWT.
     *
     * @param  array<string, mixed>         $claims Application payload claims.
     * @return array{jwt: string, renewAfter: int}
     */
    public function getToken(string $audience, string $subject, array $claims = []): array;

    /**
     * Validate standard claims (issuer, audience, expiry).
     * Never throws — returns false for any invalid or expired token.
     */
    public function validate(string $rawToken, string $audience): bool;

    /**
     * Verify the token signature only (no expiry / audience check).
     * Never throws — returns false for any unsatisfied constraint.
     */
    public function verify(string $rawToken): bool;

    /**
     * Return only non-standard (application-level) claims.
     *
     * @return array<string, mixed>
     */
    public function getFilteredClaims(string $rawToken): array;
}
