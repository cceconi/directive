<?php

declare(strict_types=1);

use Directive\Service\Logging\DirectiveLogger;
use Directive\Service\Logging\RequestIdHolder;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;

/**
 * Build a DirectiveLogger with a TestHandler injected for assertion.
 */
function makeTestLogger(): array
{
    $loggingConfig = makeTestLoggingConfig();

    $logger = new DirectiveLogger($loggingConfig, new RequestIdHolder());

    $testHandler = new TestHandler(Level::Debug, bubble: false);
    // Prepend TestHandler so it captures records before StreamHandler
    $logger->setHandlers([$testHandler]);

    return [$logger, $testHandler];
}

describe('DirectiveLogger — semantic helpers', function (): void {

    it('logRequest emits http.request at DEBUG', function (): void {
        [$logger, $handler] = makeTestLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $request = new ServerRequest('GET', 'https://example.com/api/v1/users');
        $logger->logRequest($request);

        expect($handler->hasDebugRecords())->toBeTrue();
        $record = $handler->getRecords()[0];
        expect($record['message'])->toBe('http.request');
        expect($record['context']['method'])->toBe('GET');
        expect($record['context']['uri'])->toBe('https://example.com/api/v1/users');
    });

    it('logResponse emits http.response at DEBUG', function (): void {
        [$logger, $handler] = makeTestLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $response = new Response(201);
        $logger->logResponse($response);

        expect($handler->hasDebugRecords())->toBeTrue();
        $record = $handler->getRecords()[0];
        expect($record['message'])->toBe('http.response');
        expect($record['context']['status'])->toBe(201);
    });

    it('logWebUser is a no-op when user is null', function (): void {
        [$logger, $handler] = makeTestLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $logger->logWebUser(null);

        expect($handler->getRecords())->toBeEmpty();
    });

    it('logWebUser is a no-op when user is a guest', function (): void {
        [$logger, $handler] = makeTestLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $user = new class implements \Directive\Service\Security\WebUserInterface {
            public function getRole(): \Directive\Application\Role\AbstractRole
            {
                return new class extends \Directive\Application\Role\AbstractRole {
                    public function getPermission(string $useCase): \Directive\Application\Role\Permission
                    {
                        return \Directive\Application\Role\Permission::Forbidden;
                    }
                };
            }
            public function isAuthenticated(): bool
            {
                return false;
            }
            public function isGuest(): bool
            {
                return true;
            }
            public function getId(): string
            {
                return '';
            }
            public function getFullName(): string
            {
                return '';
            }
            public function loadFromClaims(mixed $claims): void {}
            /** @return array<string, mixed> */
            public function getAuthenticatedData(): array
            {
                return [];
            }
            /** @return array<string, mixed> */
            public function getAnonymousData(): array
            {
                return [];
            }
        };

        $logger->logWebUser($user);

        expect($handler->getRecords())->toBeEmpty();
    });

    it('logError emits the throwable class at ERROR', function (): void {
        [$logger, $handler] = makeTestLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $e = new \RuntimeException('oops', 42);
        $logger->logError($e);

        expect($handler->hasErrorRecords())->toBeTrue();
        $record = $handler->getRecords()[0];
        expect($record['message'])->toBe(\RuntimeException::class);
        expect($record['context']['message'])->toBe('oops');
        expect($record['context']['code'])->toBe(42);
    });

    it('logBenchmark emits benchmark at DEBUG', function (): void {
        [$logger, $handler] = makeTestLogger();
        /** @var DirectiveLogger $logger */
        /** @var TestHandler $handler */

        $points = [[['type' => 'db', 'ms' => 12]]];
        $logger->logBenchmark($points, 0.123);

        expect($handler->hasDebugRecords())->toBeTrue();
        $record = $handler->getRecords()[0];
        expect($record['message'])->toBe('benchmark');
        expect($record['context']['points'])->toBe($points);
        expect($record['context']['time_execution'])->toBe(0.123);
    });
});
