<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Cli\DirectiveCommand;
use Directive\Exception\ConfigurationException;
use Directive\Service\Configuration\AbstractConfiguration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays all declared configuration variables with their resolved values and source origins.
 *
 * All service config keys (logging, security, http, …) are declared into the single shared
 * AbstractConfiguration dictionary, so this command shows every key in one table.
 *
 * Columns: Variable | Value | Source
 * Sensitive keys (containing SECRET, PASSWORD, KEY, TOKEN, …) are masked as ***.
 */
#[AsCommand(
    name: 'config:audit',
    description: 'Show all configuration variables with their resolved values and source origins.',
)]
final class ConfigAuditCommand extends DirectiveCommand
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
        $table->setHeaders(['Variable', 'Value', 'Source']);

        foreach ($definitions as $key => $def) {
            $value = array_key_exists($key, $resolved) ? $resolved[$key] : $def['default'];

            $displayValue = preg_match(self::SENSITIVE_PATTERN, $key)
                ? '***'
                : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);

            try {
                $source = $config->getSource($key);
            } catch (ConfigurationException) {
                $source = 'n/a';
            }

            $table->addRow([$key, $displayValue, $source]);
        }

        $table->render();

        return self::SUCCESS;
    }
}
