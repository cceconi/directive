<?php

declare(strict_types=1);

use Directive\Application\Message\ResultInterface;
use Directive\Application\Role\SystemRole;
use Directive\Application\User\DomainUser;
use Directive\Cli\AbstractConsoleCommand;
use Directive\Cli\Validator\AbstractCommandInputValidator;
use Directive\Cli\Validator\CommandInputEntity;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;

// ---------------------------------------------------------------------------
// Test doubles
// ---------------------------------------------------------------------------

final class StubResult implements ResultInterface
{
    public function getData(): mixed { return ['ok' => true]; }
}

function makeConsoleContainer(bool $hasLogger = false): ContainerInterface
{
    return new class($hasLogger) implements ContainerInterface {
        public function __construct(private readonly bool $hasLogger) {}

        public function get(string $id): mixed { return null; }

        public function has(string $id): bool
        {
            return $this->hasLogger && $id === \Psr\Log\LoggerInterface::class;
        }
    };
}

/** Build a concrete AbstractConsoleCommand with a given handle() body. */
function makeConsoleCommand(
    callable $handleFn,
    ?AbstractCommandInputValidator $validator = null,
    bool $hasLogger = false,
): AbstractConsoleCommand {
    return new class($handleFn, $validator, makeConsoleContainer($hasLogger)) extends AbstractConsoleCommand {
        public function __construct(
            private readonly \Closure $handleFn,
            ?AbstractCommandInputValidator $v,
            ContainerInterface $container,
        ) {
            $this->validator = $v;
            parent::__construct($container);
        }

        protected function configure(): void
        {
            $this->setName('test:console');
        }

        protected function handle(): ResultInterface
        {
            return ($this->handleFn)();
        }
    };
}

// ---------------------------------------------------------------------------
// Validation lifecycle
// ---------------------------------------------------------------------------

describe('AbstractConsoleCommand — validation lifecycle', function () {
    it('calls handle() and returns SUCCESS when validator passes', function () {
        $called    = false;
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void {} // no fields → always passes
        };
        $validator->setInput(new ArrayInput([]));

        $cmd    = makeConsoleCommand(function () use (&$called): StubResult {
            $called = true;
            return new StubResult();
        }, $validator);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($called)->toBeTrue();
        expect($tester->getStatusCode())->toBe(Command::SUCCESS);
    });

    it('returns INVALID without calling handle() when validation fails', function () {
        $called    = false;
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void
            {
                $this->scalar('name', new \Directive\Input\SimpleString(), required: true);
            }
        };
        // No input given — required field is missing.
        $validator->setInput(new ArrayInput([]));
        // Pre-call getCommandInputEntity so errors are populated.
        $validator->getCommandInputEntity();

        $cmd    = makeConsoleCommand(function () use (&$called): StubResult {
            $called = true;
            return new StubResult();
        }, $validator);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($called)->toBeFalse();
        expect($tester->getStatusCode())->toBe(Command::INVALID);
    });

    it('returns SUCCESS and calls handle() when no validator is set', function () {
        $called = false;
        $cmd    = makeConsoleCommand(function () use (&$called): StubResult {
            $called = true;
            return new StubResult();
        });

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($called)->toBeTrue();
        expect($tester->getStatusCode())->toBe(Command::SUCCESS);
    });

    it('returns FAILURE when handle() throws a Throwable', function () {
        $cmd = makeConsoleCommand(function (): never {
            throw new \RuntimeException('Domain error');
        });

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(Command::FAILURE);
        expect($tester->getDisplay())->toContain('Domain error');
    });
});

// ---------------------------------------------------------------------------
// domainUser()
// ---------------------------------------------------------------------------

describe('AbstractConsoleCommand — domainUser()', function () {
    it('returns a DomainUser with SystemRole', function () {
        $cmd = makeConsoleCommand(function (): StubResult { return new StubResult(); });

        $ref  = new \ReflectionMethod($cmd, 'domainUser');
        $user = $ref->invoke($cmd);

        expect($user)->toBeInstanceOf(DomainUser::class);
        expect($user->role)->toBeInstanceOf(SystemRole::class);
    });

    it('does not require WebUserInterface in the container', function () {
        // Container has nothing bound — should still work.
        $cmd = makeConsoleCommand(function (): StubResult { return new StubResult(); });
        $ref = new \ReflectionMethod($cmd, 'domainUser');

        expect(fn () => $ref->invoke($cmd))->not->toThrow(\Throwable::class);
    });
});
