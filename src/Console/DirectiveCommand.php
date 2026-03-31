<?php

declare(strict_types=1);

namespace Directive\Console;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for all Directive console commands.
 *
 * Subclasses implement executeCommand() and return a Symfony Console
 * exit code (Command::SUCCESS, Command::FAILURE, Command::INVALID).
 */
abstract class DirectiveCommand extends Command
{
    public function __construct(
        protected readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    // ------------------------------------------------------------------
    // Symfony Command lifecycle
    // ------------------------------------------------------------------

    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $this->getName() ?? 'unknown';

        // Resolve structured logger lazily — container is always built before execute() runs.
        $logger = null;
        if ($this->container->has(LoggerInterface::class)) {
            /** @var LoggerInterface $logger */
            $logger = $this->container->get(LoggerInterface::class);
            $logger->info('console.command.start', [
                'name'      => $name,
                'arguments' => $input->getArguments(),
                'options'   => $input->getOptions(),
            ]);
        }

        $output->writeln(sprintf('[directive] Running command: <info>%s</info>', $name));

        $code = $this->executeCommand($input, $output);

        $label = $code === self::SUCCESS ? '<info>SUCCESS</info>' : '<error>FAILURE</error>';
        $output->writeln(sprintf('[directive] Command <comment>%s</comment> finished: %s', $name, $label));

        if ($logger !== null) {
            $logger->info('console.command.end', [
                'name'   => $name,
                'result' => $code === self::SUCCESS ? 'success' : 'failure',
            ]);
        }

        return $code;
    }

    // ------------------------------------------------------------------
    // Contract for subclasses
    // ------------------------------------------------------------------

    /**
     * Implement your command logic here.
     *
     * @return int One of Command::SUCCESS, Command::FAILURE, Command::INVALID
     */
    abstract protected function executeCommand(InputInterface $input, OutputInterface $output): int;
}
