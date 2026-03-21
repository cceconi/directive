<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

use Directive\Exception\SecurityException;
use Lcobucci\JWT\Configuration;

/**
 * Contract for lcobucci/jwt v5 Configuration factory.
 */
interface AlgorithmFactoryInterface
{
    /**
     * Build a pre-configured JWT Configuration for the given algorithm.
     *
     * @param string $alg          Algorithm constant (e.g. 'HS256', 'RS256', 'ES256').
     * @param string $signingKey   Plain-text secret (HMAC) or file path (RSA/ECDSA).
     * @param string $verifyKey    Same as $signingKey for HMAC; public key path for RSA/ECDSA.
     * @param string $passphrase   Key passphrase (RSA/ECDSA private key only).
     *
     * @throws SecurityException When the algorithm is unknown or signing key is empty.
     */
    public function buildConfiguration(
        string $alg,
        string $signingKey,
        string $verifyKey = '',
        string $passphrase = '',
    ): Configuration;
}
