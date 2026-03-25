<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Service\Configuration\AbstractConfiguration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists all declared configuration keys with their resolved values.
 * Sensitive keys (containing SECRET, PASSWORD, KEY, TOKEN, …) are masked.
 */
#[AsCommand(
    name: 'config:list',
    description: 'List all declared configuration keys and their current values.',
)]
final class ConfigListCommand extends DirectiveCommand
{
    private const SENSITIVE_PATTERN = '/SECRET|PASSWORD|PASSWD|PWD|KEY|TOKEN|CREDENTIAL|PRIVATE|CERT/i';

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->container->has(AbstractConfiguration::class)) {
            $output->writeln('<comment>No AbstractConfiguration bound in the container.</comment>');

            return self::SUCCESS;
        }

        /** @var AbstractConfiguration $config */
        $config = $this->container->get(AbstractConfiguration::class);

        $config->audit();

        $definitions = $config->getDefinitions();
        $resolved    = $config->getAll();

        $table = new Table($output);
        $table->setHeaders(['Key', 'Type', 'Required', 'Value']);

        foreach ($definitions as $key => $def) {
            $value = array_key_exists($key, $resolved) ? $resolved[$key] : $def['default'];

            $displayValue = preg_match(self::SENSITIVE_PATTERN, $key)
                ? '***'
                : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);

            $table->addRow([
                $key,
                $def['type'],
                $def['required'] ? 'yes' : 'no',
                $displayValue,
            ]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
