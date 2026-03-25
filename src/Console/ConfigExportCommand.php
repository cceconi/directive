<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Service\Configuration\AbstractConfiguration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates a .env.example file from the declared configuration.
 *
 * Each declared variable is rendered as `VAR_NAME=` (empty value) preceded
 * by a comment indicating its type and required/optional status.
 * Current $_ENV values are intentionally never written to the output.
 */
#[AsCommand(
    name: 'config:export',
    description: 'Generate a .env.example file from the declared configuration.',
)]
final class ConfigExportCommand extends DirectiveCommand
{
    protected function configure(): void
    {
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output file path',
            '.env.example',
        );
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->container->has(AbstractConfiguration::class)) {
            $output->writeln('<comment>No AbstractConfiguration bound in the container.</comment>');

            return self::SUCCESS;
        }

        /** @var AbstractConfiguration $config */
        $config = $this->container->get(AbstractConfiguration::class);

        $definitions = $config->getDefinitions();

        $lines = [];

        foreach ($definitions as $key => $def) {
            $status  = $def['required'] ? 'required' : 'optional';
            $lines[] = sprintf('# %s | type: %s', $status, $def['type']);
            $lines[] = $key . '=';
        }

        $content = implode("\n", $lines) . "\n";

        $outFile = (string) $input->getOption('output');

        if (file_put_contents($outFile, $content) === false) {
            $output->writeln(sprintf('<error>Could not write to %s</error>', $outFile));

            return self::FAILURE;
        }

        $output->writeln(sprintf('<info>%s generated (%d variable(s))</info>', $outFile, count($definitions)));

        return self::SUCCESS;
    }
}