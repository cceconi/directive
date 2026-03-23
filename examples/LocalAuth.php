<?php

declare(strict_types=1);

namespace App\Service\Security\Authentication;

use Directive\Exception\SecurityException;
use Directive\Service\Security\Authentication\AbstractAuth;
use Directive\Service\Security\Authentication\AlgorithmFactory;
use Directive\Service\Security\Authentication\TokenManager;
use Directive\Service\Security\Authentication\TokenManagerInterface;
use Psr\Clock\ClockInterface;

/**
 * REFERENCE IMPLEMENTATION — Application layer, NOT part of the framework.
 *
 * This class is an example of a concrete authentication strategy for applications
 * that build tokens using configuration values (algorithm, secret, issuer, etc.)
 * read from their own config service.
 *
 * Why this is NOT in directive/src/:
 *   - Password hashing, credential validation, and user-store lookup are all
 *     application concerns. The framework has no knowledge of how an application
 *     manages users or secrets.
 *   - Applications should copy this file into their own src/ tree, adapt the
 *     configuration keys and constructor arguments to their needs, and bind
 *     TokenManagerInterface in their DI container.
 *
 * Configuration keys used (example — adapt to your app):
 *   env.token.audience       — JWT audience claim
 *   env.token.alg            — algorithm constant (e.g. 'HS256', 'RS256')
 *   env.token.sign           — signing key: plain secret (HMAC) or file path (RSA/ECDSA)
 *   env.token.verify         — public key file path (RSA/ECDSA only; ignored for HMAC)
 *   env.token.passphrase     — private key passphrase (optional, RSA/ECDSA only)
 *   env.token.issuer         — JWT issuer claim
 *   env.token.notBeforeOffset — seconds offset for nbf claim (default 0)
 *   env.token.lifetime       — token validity in seconds (default 300)
 *   env.token.renewOffset    — negative offset before expiry to signal renewal (default -60)
 *   env.token.idComplexity   — random bytes for jti (default 24)
 */
final class LocalAuth extends AbstractAuth
{
    /**
     * @param array<string, mixed> $config Flat config array keyed by the env.token.* names above.
     */
    public function __construct(
        array $config,
        ClockInterface $clock,
    ) {
        $factory   = new AlgorithmFactory();
        $jwtConfig = $factory->buildConfiguration(
            (string) ($config['env.token.alg'] ?? 'HS256'),
            (string) ($config['env.token.sign'] ?? ''),
            (string) ($config['env.token.verify'] ?? ''),
            (string) ($config['env.token.passphrase'] ?? ''),
        );

        $tokenManager = new TokenManager(
            $jwtConfig,
            (string) ($config['env.token.issuer'] ?? ''),
            $clock,
            (int) ($config['env.token.notBeforeOffset'] ?? 0),
            (int) ($config['env.token.lifetime'] ?? 300),
            (int) ($config['env.token.renewOffset'] ?? -60),
            (int) ($config['env.token.idComplexity'] ?? 24),
        );

        parent::__construct($tokenManager, (string) ($config['env.token.audience'] ?? ''));
    }
}