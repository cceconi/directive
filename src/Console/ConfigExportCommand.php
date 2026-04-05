<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Cli\DirectiveCommand;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Configuration\Configuration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates a .env.example file (default) or a config-schema.json (--schema flag).
 *
 * Default mode:
 *   Each declared variable is rendered as `VAR_NAME=` (empty value) preceded
 *   by a comment indicating its type and required/optional status.
 *   Current $_ENV values are intentionally never written to the output.
 *
 * Schema mode (--schema):
 *   Generates config-schema.json — a machine-readable description of all declared
 *   configuration variables, suitable for Doppler/Infisical/CI integration.
 *   Use --version to embed an explicit app version in the schema.
 */
#[AsCommand(
    name: 'config:export',
    description: 'Generate a .env.example file (or config-schema.json with --schema) from the declared configuration.',
)]
final class ConfigExportCommand extends DirectiveCommand
{
    private const SCHEMA_FILE = 'config-schema.json';

    protected function configure(): void
    {
        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output file path (default mode only)',
            '.env.example',
        );

        $this->addOption(
            'schema',
            null,
            InputOption::VALUE_NONE,
            'Generate config-schema.json instead of .env.example',
        );

        $this->addOption(
            'appVersion',
            null,
            InputOption::VALUE_REQUIRED,
            'Override appVersion embedded in the schema (--schema mode only)',
        );
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->container->has(Configuration::class)) {
            $output->writeln('<comment>No Configuration bound in the container.</comment>');

            return self::SUCCESS;
        }

        if ($input->getOption('schema')) {
            return $this->exportSchema($input, $output);
        }

        return $this->exportEnvExample($input, $output);
    }

    // ------------------------------------------------------------------
    // Default mode — .env.example
    // ------------------------------------------------------------------

    private function exportEnvExample(InputInterface $input, OutputInterface $output): int
    {
        /** @var Configuration $config */
        $config = $this->container->get(Configuration::class);

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

    // ------------------------------------------------------------------
    // Schema mode — config-schema.json
    // ------------------------------------------------------------------

    private function exportSchema(InputInterface $input, OutputInterface $output): int
    {
        /** @var Configuration $config */
        $config = $this->container->get(Configuration::class);

        // Resolve appVersion: explicit --appVersion flag overrides the DI-injected identity config
        $versionOverride = $input->getOption('appVersion');

        if (is_string($versionOverride) && $versionOverride !== '') {
            $appVersion = $versionOverride;
        } elseif ($this->container->has(AppIdentityConfigInterface::class)) {
            /** @var AppIdentityConfigInterface $identity */
            $identity   = $this->container->get(AppIdentityConfigInterface::class);
            $appVersion = $identity->getAppVersion();
        } else {
            $appVersion = '0.0.0';
        }

        $definitions = $config->getDefinitions();

        $variables = [];

        foreach ($definitions as $key => $def) {
            $entry = [
                'key'      => $key,
                'type'     => $def['type'],
                'required' => $def['required'],
            ];

            if ($def['allowed'] !== []) {
                $entry['allowed'] = $def['allowed'];
            }

            if (!$def['required']) {
                $entry['default'] = $def['default'];
            }

            $variables[] = $entry;
        }

        $schema = [
            'appVersion'  => $appVersion,
            'generatedAt' => new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            'variables'   => $variables,
        ];

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (file_put_contents(self::SCHEMA_FILE, $json) === false) {
            $output->writeln(sprintf('<error>Could not write to %s</error>', self::SCHEMA_FILE));

            return self::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>%s generated (appVersion: %s, %d variable(s))</info>',
            self::SCHEMA_FILE,
            $appVersion,
            count($definitions),
        ));

        return self::SUCCESS;
    }
}
