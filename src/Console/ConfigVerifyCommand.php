<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Cli\DirectiveCommand;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigurationVaultInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cross-checks declared configuration keys against a secrets vault.
 *
 * Reports keys that are declared in Configuration but absent from
 * the vault, and vault keys that are not declared in the configuration.
 */
#[AsCommand(
    name: 'config:verify',
    description: 'Cross-check configuration keys against the secrets vault.',
)]
final class ConfigVerifyCommand extends DirectiveCommand
{
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->container->has(Configuration::class)) {
            $output->writeln('<comment>No Configuration bound in the container.</comment>');

            return self::SUCCESS;
        }

        if (!$this->container->has(ConfigurationVaultInterface::class)) {
            $output->writeln('<comment>No ConfigurationVaultInterface bound in the container — skipping vault cross-check.</comment>');

            return self::SUCCESS;
        }

        /** @var Configuration $config */
        $config = $this->container->get(Configuration::class);

        /** @var ConfigurationVaultInterface $vault */
        $vault = $this->container->get(ConfigurationVaultInterface::class);

        $configKeys = array_keys($config->getDefinitions());
        $vaultKeys  = $vault->getKeys();

        $missingFromVault  = array_diff($configKeys, $vaultKeys);
        $missingFromConfig = array_diff($vaultKeys, $configKeys);

        $ok = true;

        if ($missingFromVault !== []) {
            $ok = false;
            $output->writeln('<error>Keys declared in config but absent from vault:</error>');

            foreach ($missingFromVault as $key) {
                $output->writeln('  - ' . $key);
            }
        }

        if ($missingFromConfig !== []) {
            $ok = false;
            $output->writeln('<comment>Keys present in vault but not declared in config:</comment>');

            foreach ($missingFromConfig as $key) {
                $output->writeln('  - ' . $key);
            }
        }

        if ($ok) {
            $output->writeln('<info>Configuration and vault are in sync.</info>');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
