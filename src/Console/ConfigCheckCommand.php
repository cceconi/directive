<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Cli\DirectiveCommand;
use Directive\Service\Configuration\AbstractConfiguration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Performs a dry-run audit of the application configuration.
 *
 * Calls AbstractConfiguration::audit() and reports success or the list
 * of validation errors without stopping the process.
 */
#[AsCommand(
    name: 'config:check',
    description: 'Validate the application configuration (dry-run audit).',
)]
final class ConfigCheckCommand extends DirectiveCommand
{
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->container->has(AbstractConfiguration::class)) {
            $output->writeln('<comment>No AbstractConfiguration bound in the container — nothing to check.</comment>');

            return self::SUCCESS;
        }

        /** @var AbstractConfiguration $config */
        $config = $this->container->get(AbstractConfiguration::class);

        try {
            $config->audit();
            $output->writeln('<info>Configuration is valid.</info>');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Configuration errors detected:</error>');
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }
    }
}
