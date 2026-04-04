<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Cli\DirectiveCommand;
use Directive\Exception\ConfigurationException;
use Directive\Http\Middleware\HttpConfigInterface;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Logging\LoggingConfigInterface;
use Directive\Service\Security\Antivirus\AntivirusConfigInterface;
use Directive\Service\Security\SecurityConfigInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Displays all declared configuration variables with their resolved values and source origins.
 *
 * Columns: Variable | Value | Source
 * Sensitive keys (containing SECRET, PASSWORD, KEY, TOKEN, ...) are masked as ***.
 */
#[AsCommand(
    name: 'config:audit',
    description: 'Show all configuration variables with their resolved values and source origins.',
)]
final class ConfigAuditCommand extends DirectiveCommand
{
    private const SENSITIVE_PATTERN = '/SECRET|PASSWORD|PASSWD|PWD|KEY|TOKEN|CREDENTIAL|PRIVATE|CERT/i';

    /** @var list<string> */
    private const SERVICE_INTERFACES = [
        LoggingConfigInterface::class,
        AppIdentityConfigInterface::class,
        AntivirusConfigInterface::class,
        SecurityConfigInterface::class,
        HttpConfigInterface::class,
    ];

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $configs = $this->resolveConfigs();

        if ($configs === []) {
            $output->writeln('<comment>No AbstractConfiguration bound in the container.</comment>');

            return self::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['Variable', 'Value', 'Source']);

        foreach ($configs as $config) {
            $config->audit();

            $definitions = $config->getDefinitions();
            $resolved    = $config->getAll();

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
        }

        $table->render();

        return self::SUCCESS;
    }

    /**
     * Collect all AbstractConfiguration instances available in the container:
     * the user's app config + all Default*Config service implementations.
     *
     * @return list<AbstractConfiguration>
     */
    private function resolveConfigs(): array
    {
        $ids = array_merge([AbstractConfiguration::class], self::SERVICE_INTERFACES);

        $configs = [];
        $seen    = [];

        foreach ($ids as $id) {
            if (!$this->container->has($id)) {
                continue;
            }

            $obj = $this->container->get($id);

            if (!($obj instanceof AbstractConfiguration)) {
                continue;
            }

            $class = get_class($obj);

            if (isset($seen[$class])) {
                continue;
            }

            $seen[$class] = true;
            $configs[]    = $obj;
        }

        return $configs;
    }
}
