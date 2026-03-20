<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Service\Logging\WebLoggerInterface;

/**
 * Local JWT authentication using lcobucci/jwt v5.
 *
 * Configuration keys used:
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
    private readonly TokenManager $tokenManager;

    public function __construct(
        ConfigurationInterface $config,
        WebLoggerInterface $logger,
    ) {
        parent::__construct($config, $logger);

        $factory   = new AlgorithmFactory();
        $jwtConfig = $factory->buildConfiguration(
            (string) $this->config->get('env.token.alg', 'HS256'),
            (string) $this->config->get('env.token.sign', ''),
            (string) $this->config->get('env.token.verify', ''),
            (string) $this->config->get('env.token.passphrase', ''),
        );

        $this->tokenManager = new TokenManager(
            $jwtConfig,
            (string) $this->config->get('env.token.issuer', ''),
            (int)    $this->config->get('env.token.notBeforeOffset', 0),
            (int)    $this->config->get('env.token.lifetime', 300),
            (int)    $this->config->get('env.token.renewOffset', -60),
            (int)    $this->config->get('env.token.idComplexity', 24),
        );
    }

    // ------------------------------------------------------------------
    // AuthInterface
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $claims */
    public function generateToken(string $subject, array $claims = []): array
    {
        return $this->tokenManager->getToken(
            (string) $this->config->get('env.token.audience', ''),
            $subject,
            $claims,
        );
    }

    public function validateToken(string $token): bool
    {
        try {
            return $this->tokenManager->validate(
                $token,
                (string) $this->config->get('env.token.audience', ''),
            );
        } catch (\Throwable $e) {
            $this->logger->logError($e);
            return false;
        }
    }

    public function verifyToken(string $token): bool
    {
        try {
            return $this->tokenManager->verify($token);
        } catch (\Throwable $e) {
            $this->logger->logError($e);
            return false;
        }
    }

    /** @return array<string, mixed> */
    public function getTokenClaims(string $token): array
    {
        try {
            return $this->tokenManager->getFilteredClaims($token);
        } catch (\Throwable $e) {
            $this->logger->logError($e);
            return [];
        }
    }

    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }
}
