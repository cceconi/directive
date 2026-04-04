<?php

declare(strict_types=1);

namespace Directive\Examples;

use Directive\Console\DirectiveCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Bundled no-op command confirming the console is wired correctly.
 *
 * Run with: php bin/apisy app:dummy
 */
#[AsCommand(
    name: 'app:dummy',
    description: 'Verify the Directive console is working.',
)]
final class ExampleCommand extends DirectiveCommand
{
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Directive console is up and running.</info>');

        return self::SUCCESS;
    }
}
