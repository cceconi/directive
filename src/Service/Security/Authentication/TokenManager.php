<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

use Directive\Exception\SecurityException;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Psr\Clock\ClockInterface;

/**
 * JWT generation, validation and claim extraction — lcobucci/jwt v5.
 *
 * One instance = one algorithm configuration (symmetric or asymmetric).
 * Create via AlgorithmFactory::buildConfiguration() + inject here.
 */
final class TokenManager
{
    /** Standard JWT registered claims — filtered out of application claims. */
    private const STANDARD_CLAIMS = ['iss', 'iat', 'nbf', 'exp', 'aud', 'sub', 'jti'];

    public function __construct(
        private readonly Configuration $jwtConfig,
        private readonly string $issuer,
        private readonly int $notBeforeOffset = 0,
        private readonly int $lifetime = 300,
        private readonly int $renewOffset = -60,
        private readonly int $idComplexity = 24,
    ) {}

    // ------------------------------------------------------------------
    // Token generation
    // ------------------------------------------------------------------

    /**
     * Build and sign a JWT.
     *
     * @param array<string, mixed> $claims Application payload claims.
     * @return array{jwt: string, renewAfter: int}
     */
    public function getToken(string $audience, string $subject, array $claims = []): array
    {
        if ($this->issuer === '') {
            throw new SecurityException('JWT issuer cannot be empty.');
        }
        if ($audience === '') {
            throw new SecurityException('JWT audience cannot be empty.');
        }
        if ($subject === '') {
            throw new SecurityException('JWT subject cannot be empty.');
        }

        $now        = new DateTimeImmutable();
        $renewAfter = $this->lifetime + $this->renewOffset;
        $complexity = max(1, $this->idComplexity);

        $builder = $this->jwtConfig->builder()
            ->issuedBy($this->issuer)
            ->permittedFor($audience)
            ->relatedTo($subject)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now->modify(sprintf('%+d seconds', $this->notBeforeOffset)))
            ->expiresAt($now->modify(sprintf('+%d seconds', $this->lifetime)))
            ->identifiedBy(bin2hex(random_bytes($complexity)));

        foreach ($claims as $name => $value) {
            if ($name !== '') {
                $builder = $builder->withClaim($name, $value);
            }
        }

        $token = $builder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return [
            'jwt'        => $token->toString(),
            'renewAfter' => $renewAfter,
        ];
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    /**
     * Validate standard claims (issuer, audience, expiry).
     */
    public function validate(string $rawToken, string $audience): bool
    {
        try {
            if ($this->issuer === '' || $audience === '' || $rawToken === '') {
                return false;
            }

            $token = $this->parse($rawToken);

            return $this->jwtConfig->validator()->validate(
                $token,
                new IssuedBy($this->issuer),
                new PermittedFor($audience),
                new StrictValidAt($this->systemClock()),
            );
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Verify the token signature.
     */
    public function verify(string $rawToken): bool
    {
        try {
            $token = $this->parse($rawToken);

            return $this->jwtConfig->validator()->validate(
                $token,
                new SignedWith($this->jwtConfig->signer(), $this->jwtConfig->verificationKey()),
            );
        } catch (\Throwable) {
            return false;
        }
    }

    // ------------------------------------------------------------------
    // Claims
    // ------------------------------------------------------------------

    /**
     * Return only non-standard (application-level) claims.
     *
     * @return array<string, mixed>
     */
    public function getFilteredClaims(string $rawToken): array
    {
        $token  = $this->parse($rawToken);
        $all    = $token->claims()->all();
        $result = [];

        foreach ($all as $key => $value) {
            if (!in_array($key, self::STANDARD_CLAIMS, strict: true)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Parse and return the full token (used by Access types to read claims).
     */
    public function parseToken(string $rawToken): UnencryptedToken
    {
        return $this->parse($rawToken);
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    private function parse(string $rawToken): UnencryptedToken
    {
        if ($rawToken === '') {
            throw new SecurityException('JWT token string cannot be empty.');
        }

        $token = $this->jwtConfig->parser()->parse($rawToken);

        if (!($token instanceof UnencryptedToken)) {
            throw new SecurityException('Encrypted tokens are not supported.');
        }

        return $token;
    }

    private function systemClock(): ClockInterface
    {
        return new class implements ClockInterface {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable();
            }
        };
    }
}
