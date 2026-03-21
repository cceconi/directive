<?php

declare(strict_types=1);

use Directive\Exception\SecurityException;
use Directive\Service\Security\Authentication\AlgorithmFactory;
use Directive\Service\Security\Authentication\TokenManager;
use Psr\Clock\ClockInterface;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeTokenManager(int $lifetime = 300, ?ClockInterface $clock = null): TokenManager
{
    $factory   = new AlgorithmFactory();
    $jwtConfig = $factory->buildConfiguration('HS256', 'directive-secret-key-for-testing');

    return new TokenManager(
        jwtConfig: $jwtConfig,
        issuer: 'directive-test',
        clock: $clock ?? new class implements ClockInterface {
            public function now(): \DateTimeImmutable
            {
                return new \DateTimeImmutable();
            }
        },
        lifetime: $lifetime,
    );
}

function frozenClock(\DateTimeImmutable $at): ClockInterface
{
    return new class ($at) implements ClockInterface {
        public function __construct(private readonly \DateTimeImmutable $at) {}
        public function now(): \DateTimeImmutable
        {
            return $this->at;
        }
    };
}

// ---------------------------------------------------------------------------
// getToken / validate round-trip
// ---------------------------------------------------------------------------

describe('TokenManager::getToken', function () {
    it('returns a JWT string and a renewAfter integer', function () {
        $manager = makeTokenManager();
        $result  = $manager->getToken('my-app', 'user-42', ['role' => 'admin']);

        expect($result)->toHaveKeys(['jwt', 'renewAfter'])
            ->and($result['jwt'])->toBeString()->toContain('.')
            ->and($result['renewAfter'])->toBeInt();
    });

    it('produces a three-segment JWT', function () {
        $manager = makeTokenManager();
        $jwt     = $manager->getToken('my-app', 'user-1')['jwt'];

        expect(substr_count($jwt, '.'))->toBe(2);
    });

    it('throws SecurityException when issuer is empty', function () {
        $factory   = new AlgorithmFactory();
        $jwtConfig = $factory->buildConfiguration('HS256', 'directive-secret-key-for-testing');
        $manager   = new TokenManager(
            jwtConfig: $jwtConfig,
            issuer: '',
            clock: frozenClock(new \DateTimeImmutable()),
        );

        expect(fn() => $manager->getToken('aud', 'sub'))->toThrow(SecurityException::class);
    });

    it('throws SecurityException when audience is empty', function () {
        $manager = makeTokenManager();

        expect(fn() => $manager->getToken('', 'sub'))->toThrow(SecurityException::class);
    });

    it('throws SecurityException when subject is empty', function () {
        $manager = makeTokenManager();

        expect(fn() => $manager->getToken('aud', ''))->toThrow(SecurityException::class);
    });
});

// ---------------------------------------------------------------------------
// validate
// ---------------------------------------------------------------------------

describe('TokenManager::validate', function () {
    it('returns true for a freshly generated token', function () {
        $manager = makeTokenManager();
        $jwt     = $manager->getToken('my-app', 'user-1')['jwt'];

        expect($manager->validate($jwt, 'my-app'))->toBeTrue();
    });

    it('returns false for an expired token', function () {
        $past   = new \DateTimeImmutable('-10 minutes');
        $future = new \DateTimeImmutable('+10 minutes');

        // Generate with a clock in the past (token expired 10 min ago)
        $genManager = makeTokenManager(lifetime: 1, clock: frozenClock($past));
        $jwt        = $genManager->getToken('my-app', 'user-1')['jwt'];

        // Validate with a clock in the future
        $factory    = new AlgorithmFactory();
        $jwtConfig  = $factory->buildConfiguration('HS256', 'directive-secret-key-for-testing');
        $valManager = new TokenManager(
            jwtConfig: $jwtConfig,
            issuer: 'directive-test',
            clock: frozenClock($future),
        );

        expect($valManager->validate($jwt, 'my-app'))->toBeFalse();
    });

    it('returns false for a tampered token', function () {
        $manager = makeTokenManager();
        $jwt     = $manager->getToken('my-app', 'user-1')['jwt'];

        $parts       = explode('.', $jwt);
        $parts[1]    = base64_encode('{"sub":"hacker","iss":"directive-test","aud":["my-app"]}');
        $tamperedJwt = implode('.', $parts);

        expect($manager->validate($tamperedJwt, 'my-app'))->toBeFalse();
    });

    it('returns false when audience does not match', function () {
        $manager = makeTokenManager();
        $jwt     = $manager->getToken('my-app', 'user-1')['jwt'];

        expect($manager->validate($jwt, 'other-app'))->toBeFalse();
    });

    it('returns false for an empty token string', function () {
        $manager = makeTokenManager();

        expect($manager->validate('', 'my-app'))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// verify
// ---------------------------------------------------------------------------

describe('TokenManager::verify', function () {
    it('returns true for a validly signed token', function () {
        $manager = makeTokenManager();
        $jwt     = $manager->getToken('my-app', 'user-1')['jwt'];

        expect($manager->verify($jwt))->toBeTrue();
    });

    it('returns false when token is signed with a different key', function () {
        $factory = new AlgorithmFactory();
        $config  = $factory->buildConfiguration('HS256', 'other-key-for-directive-testing!');
        $other   = new TokenManager(
            jwtConfig: $config,
            issuer: 'directive-test',
            clock: frozenClock(new \DateTimeImmutable()),
        );
        $jwt = $other->getToken('my-app', 'user-1')['jwt'];

        expect(makeTokenManager()->verify($jwt))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// getFilteredClaims
// ---------------------------------------------------------------------------

describe('TokenManager::getFilteredClaims', function () {
    it('returns application claims without standard JWT fields', function () {
        $manager = makeTokenManager();
        $jwt     = $manager->getToken('my-app', 'user-1', ['role' => 'editor', 'tenant' => 'acme'])['jwt'];
        $claims  = $manager->getFilteredClaims($jwt);

        expect($claims)->toHaveKeys(['role', 'tenant'])
            ->and($claims['role'])->toBe('editor')
            ->and($claims['tenant'])->toBe('acme')
            ->and($claims)->not->toHaveKey('iss')
            ->and($claims)->not->toHaveKey('exp');
    });

    it('throws SecurityException for an invalid token', function () {
        $manager = makeTokenManager();

        expect(fn() => $manager->getFilteredClaims('not.a.token'))->toThrow(SecurityException::class);
    });

    it('returns empty array when no application claims are present', function () {
        $manager = makeTokenManager();
        $jwt     = $manager->getToken('my-app', 'user-1')['jwt'];

        expect($manager->getFilteredClaims($jwt))->toBe([]);
    });
});
