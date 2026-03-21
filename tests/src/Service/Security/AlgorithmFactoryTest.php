<?php

declare(strict_types=1);

use Directive\Exception\SecurityException;
use Directive\Service\Security\Authentication\AlgorithmFactory;
use Lcobucci\JWT\Configuration;

describe('AlgorithmFactory::buildConfiguration', function () {
    it('builds a Configuration for HS256', function () {
        $factory = new AlgorithmFactory();
        $config  = $factory->buildConfiguration('HS256', 'my-hmac-secret');

        expect($config)->toBeInstanceOf(Configuration::class);
    });

    it('builds a Configuration for HS384', function () {
        $factory = new AlgorithmFactory();
        $config  = $factory->buildConfiguration('HS384', 'my-hmac-secret');

        expect($config)->toBeInstanceOf(Configuration::class);
    });

    it('builds a Configuration for HS512', function () {
        $factory = new AlgorithmFactory();
        $config  = $factory->buildConfiguration('HS512', 'my-hmac-secret');

        expect($config)->toBeInstanceOf(Configuration::class);
    });

    it('is case-insensitive for algorithm names', function () {
        $factory = new AlgorithmFactory();
        $config  = $factory->buildConfiguration('hs256', 'my-secret');

        expect($config)->toBeInstanceOf(Configuration::class);
    });

    it('throws SecurityException for an unknown algorithm', function () {
        $factory = new AlgorithmFactory();

        expect(fn() => $factory->buildConfiguration('XYZ256', 'key'))
            ->toThrow(SecurityException::class);
    });

    it('throws SecurityException when signing key is empty', function () {
        $factory = new AlgorithmFactory();

        expect(fn() => $factory->buildConfiguration('HS256', ''))
            ->toThrow(SecurityException::class);
    });

    it('builds a Configuration for RS256 using generated RSA key files', function () {
        $res = openssl_pkey_new(['private_key_bits' => 1024, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        assert($res !== false);

        openssl_pkey_export($res, $privateKeyPem);
        $publicKeyPem = openssl_pkey_get_details($res)['key'];

        $privateFile = tempnam(sys_get_temp_dir(), 'directive_rs256_priv_');
        $publicFile  = tempnam(sys_get_temp_dir(), 'directive_rs256_pub_');
        file_put_contents($privateFile, $privateKeyPem);
        file_put_contents($publicFile, $publicKeyPem);

        try {
            $factory = new AlgorithmFactory();
            $config  = $factory->buildConfiguration('RS256', $privateFile, $publicFile);

            expect($config)->toBeInstanceOf(Configuration::class);
        } finally {
            @unlink($privateFile);
            @unlink($publicFile);
        }
    });

    it('produces a Configuration that can sign and verify a token (HS256 round-trip)', function () {
        $factory = new AlgorithmFactory();
        $config  = $factory->buildConfiguration('HS256', 'hs256-round-trip-secret-key-test');

        $token = $config->builder()
            ->issuedBy('test')
            ->permittedFor('aud')
            ->relatedTo('sub')
            ->getToken($config->signer(), $config->signingKey());

        $valid = $config->validator()->validate(
            $token,
            new Lcobucci\JWT\Validation\Constraint\SignedWith(
                $config->signer(),
                $config->verificationKey(),
            ),
        );

        expect($valid)->toBeTrue();
    });
});
