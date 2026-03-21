<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

use Directive\Exception\SecurityException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa\Sha256 as EcdsaSha256;
use Lcobucci\JWT\Signer\Ecdsa\Sha384 as EcdsaSha384;
use Lcobucci\JWT\Signer\Ecdsa\Sha512 as EcdsaSha512;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HmacSha256;
use Lcobucci\JWT\Signer\Hmac\Sha384 as HmacSha384;
use Lcobucci\JWT\Signer\Hmac\Sha512 as HmacSha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RsaSha256;
use Lcobucci\JWT\Signer\Rsa\Sha384 as RsaSha384;
use Lcobucci\JWT\Signer\Rsa\Sha512 as RsaSha512;

/**
 * Creates lcobucci/jwt v5 Configuration instances for each supported algorithm.
 */
final class AlgorithmFactory implements AlgorithmFactoryInterface
{
    public const ECDSA_SHA256 = 'ES256';
    public const ECDSA_SHA384 = 'ES384';
    public const ECDSA_SHA512 = 'ES512';
    public const HMAC_SHA256  = 'HS256';
    public const HMAC_SHA384  = 'HS384';
    public const HMAC_SHA512  = 'HS512';
    public const RSA_SHA256   = 'RS256';
    public const RSA_SHA384   = 'RS384';
    public const RSA_SHA512   = 'RS512';

    /** @var array<string, class-string<Signer>> */
    private const SIGNERS = [
        self::ECDSA_SHA256 => EcdsaSha256::class,
        self::ECDSA_SHA384 => EcdsaSha384::class,
        self::ECDSA_SHA512 => EcdsaSha512::class,
        self::HMAC_SHA256  => HmacSha256::class,
        self::HMAC_SHA384  => HmacSha384::class,
        self::HMAC_SHA512  => HmacSha512::class,
        self::RSA_SHA256   => RsaSha256::class,
        self::RSA_SHA384   => RsaSha384::class,
        self::RSA_SHA512   => RsaSha512::class,
    ];

    private const SYMMETRIC = [
        self::HMAC_SHA256,
        self::HMAC_SHA384,
        self::HMAC_SHA512,
    ];

    /**
     * Build a pre-configured JWT Configuration for the given algorithm.
     *
     * @param string $alg          Algorithm constant (e.g. 'HS256', 'RS256').
     * @param string $signingKey   Plain-text secret (HMAC) or file path (RSA/ECDSA).
     * @param string $verifyKey    Same as $signingKey for HMAC; public key path for RSA/ECDSA.
     * @param string $passphrase   Key passphrase (RSA/ECDSA private key only).
     *
     * @throws SecurityException When the algorithm is unknown.
     */
    public function buildConfiguration(
        string $alg,
        string $signingKey,
        string $verifyKey = '',
        string $passphrase = '',
    ): Configuration {
        $alg = strtoupper($alg);

        if ($signingKey === '') {
            throw new SecurityException('Signing key cannot be empty.');
        }

        if (!isset(self::SIGNERS[$alg])) {
            throw new SecurityException(sprintf('Unknown JWT algorithm: %s', $alg));
        }

        $signer = new (self::SIGNERS[$alg])();

        if (in_array($alg, self::SYMMETRIC, strict: true)) {
            return Configuration::forSymmetricSigner(
                $signer,
                InMemory::plainText($signingKey),
            );
        }

        // Asymmetric: file-based keys (RSA / ECDSA)
        $privateKey = InMemory::file('file://' . $signingKey, $passphrase);
        $publicKey  = InMemory::file('file://' . ($verifyKey !== '' ? $verifyKey : $signingKey));

        return Configuration::forAsymmetricSigner($signer, $privateKey, $publicKey);
    }
}
