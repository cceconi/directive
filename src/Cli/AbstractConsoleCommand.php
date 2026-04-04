<?php

declare(strict_types=1);

namespace Directive\Cli;

use Directive\Application\Message\ResultInterface;
use Directive\Application\Role\SystemRole;
use Directive\Application\User\DomainUser;
use Directive\Cli\Validator\AbstractCommandInputValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base for applicative CLI commands wired with the UseCase layer.
 *
 * This is the CLI symmetric of AbstractUseCaseApi:
 *   - executeCommand() is sealed and drives the validation / UseCase lifecycle.
 *   - handle() is the single hook for application logic.
 *
 * Note on naming: the method is called handle() instead of execute() to avoid
 * a return-type conflict with Symfony's Command::execute(InputInterface,
 * OutputInterface): int, which PHPStan (level 8) flags as a child-return-type
 * violation when the signature differs.
 *
 * Validation (optional):
 *   Set $this->validator in the constructor (or via property) to enable input
 *   validation before handle() is called. Omit it to skip validation entirely.
 *
 * Domain identity:
 *   domainUser() returns a DomainUser carrying SystemRole, usable directly by
 *   UseCases without a WebUserInterface in the container.
 */
abstract class AbstractConsoleCommand extends DirectiveCommand
{
    /**
     * Assign an AbstractCommandInputValidator in the constructor to enable
     * input validation before handle() is called.
     */
    protected ?AbstractCommandInputValidator $validator = null;

    // ------------------------------------------------------------------
    // Symfony Command lifecycle — sealed
    // ------------------------------------------------------------------

    final protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        // 1. Resolve logger lazily — optional dependency.
        $logger = null;
        if ($this->container->has(LoggerInterface::class)) {
            /** @var LoggerInterface $logger */
            $logger = $this->container->get(LoggerInterface::class);
        }

        // 2. Input validation (skipped when $validator is null).
        if ($this->validator !== null) {
            $this->validator->setInput($input);
            $this->validator->getCommandInputEntity();

            if ($this->validator->hasErrors()) {
                $errors = $this->validator->getErrors();
                $output->writeln('<error>Invalid input:</error>');
                foreach ($errors as $error) {
                    $output->writeln(sprintf('  - [%s] %s: %s', $error['type'], $error['property'], $error['message']));
                }

                if ($logger !== null) {
                    $logger->warning('console.command.invalid', [
                        'command' => $this->getName(),
                        'errors'  => $errors,
                    ]);
                }

                return Command::INVALID;
            }
        }

        // 3. Execute use-case logic.
        try {
            $result = $this->handle();

            if ($logger !== null) {
                $logger->info('console.command.result', [
                    'command' => $this->getName(),
                    'data'    => $result->getData(),
                ]);
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            if ($logger !== null) {
                $logger->error('console.command.failure', [
                    'command'   => $this->getName(),
                    'exception' => $e->getMessage(),
                    'trace'     => $e->getTraceAsString(),
                ]);
            }

            return Command::FAILURE;
        }
    }

    // ------------------------------------------------------------------
    // Domain identity helper
    // ------------------------------------------------------------------

    /**
     * Returns a DomainUser with SystemRole — usable by UseCases without
     * requiring an authenticated WebUser in the container.
     */
    final protected function domainUser(): DomainUser
    {
        return new DomainUser('system', new SystemRole());
    }

    // ------------------------------------------------------------------
    // Application hook — implement your UseCase call here
    // ------------------------------------------------------------------

    abstract protected function handle(): ResultInterface;
}
