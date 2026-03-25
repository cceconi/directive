<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Service\Configuration\AbstractFeatures;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all declared feature flags with their current state.
 */
#[AsCommand(
    name: 'feature:list',
    description: 'List all declared feature flags and their current state.',
)]
final class FeatureListCommand extends DirectiveCommand
{
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->container->has(AbstractFeatures::class)) {
            $output->writeln('<comment>No AbstractFeatures bound in the container.</comment>');

            return self::SUCCESS;
        }

        /** @var AbstractFeatures $features */
        $features = $this->container->get(AbstractFeatures::class);

        $definitions = $features->getDefinitions();

        $table = new Table($output);
        $table->setHeaders(['Feature', 'Env Key', 'Default', 'Current']);

        foreach ($definitions as $name => $def) {
            $enabled = $features->isEnabled($name);

            $table->addRow([
                $name,
                $def['envKey'],
                $def['default'] ? 'enabled' : 'disabled',
                $enabled ? '<info>enabled</info>' : '<comment>disabled</comment>',
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
