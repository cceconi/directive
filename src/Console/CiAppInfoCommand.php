<?php

declare(strict_types=1);

namespace Directive\Console;

use Directive\Cli\DirectiveCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates var/appinfo.json from CI environment data.
 *
 * Intended to be called once per deployment pipeline:
 *
 *   directive ci:appinfo \
 *     --name="my-app" \
 *     --app-version="1.2.0" \\
 *     --env="production" \
 *     --commit="a3f9c1d" \
 *     --branch="main" \
 *     --tag="v1.2.0" \
 *     --build-number="142" \
 *     --built-by="github-actions"
 *
 * All options except --name and --version fall back to common CI environment
 * variables (GitHub Actions, GitLab CI) when not explicitly provided.
 */
#[AsCommand(
    name: 'ci:appinfo',
    description: 'Generate var/appinfo.json from CI/CD build metadata.',
)]
final class CiAppInfoCommand extends DirectiveCommand
{
    private const OUTPUT_FILE = 'var/appinfo.json';

    protected function configure(): void
    {
        $this
            ->addOption('name',         null, InputOption::VALUE_REQUIRED, 'Application name')
            ->addOption('app-version',  null, InputOption::VALUE_REQUIRED, 'Semantic version (e.g. 1.2.0)')
            ->addOption('commit',       null, InputOption::VALUE_REQUIRED, 'Git commit SHA')
            ->addOption('branch',       null, InputOption::VALUE_REQUIRED, 'Git branch name')
            ->addOption('tag',          null, InputOption::VALUE_REQUIRED, 'Git tag (release name)')
            ->addOption('build-number', null, InputOption::VALUE_REQUIRED, 'CI pipeline / run number')
            ->addOption('built-by',     null, InputOption::VALUE_REQUIRED, 'CI runner or user identifier')
            ->addOption('output',  'o', InputOption::VALUE_REQUIRED, 'Output file path', self::OUTPUT_FILE);
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $name    = (string) $input->getOption('name');
        $version = (string) $input->getOption('app-version');

        if ($name === '') {
            $output->writeln('<error>--name is required.</error>');
            return self::INVALID;
        }

        if ($version === '') {
            $output->writeln('<error>--app-version is required.</error>');
            return self::INVALID;
        }

        $data = [
            'name'        => $name,
            'version'     => $version,
            'commitId'    => $this->resolve($input, 'commit',       'GITHUB_SHA',         'CI_COMMIT_SHA'),
            'branch'      => $this->resolve($input, 'branch',       'GITHUB_REF_NAME',    'CI_COMMIT_REF_NAME'),
            'tag'         => $this->resolve($input, 'tag',          'GITHUB_REF',         'CI_COMMIT_TAG'),
            'buildNumber' => $this->resolve($input, 'build-number', 'GITHUB_RUN_NUMBER',  'CI_PIPELINE_IID'),
            'builtBy'     => $this->resolve($input, 'built-by',     'GITHUB_ACTOR',       'GITLAB_USER_LOGIN'),
            'builtAt'     => date('c'),
        ];

        $outputFile = (string) $input->getOption('output');

        $dir = dirname($outputFile);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $output->writeln('<error>Failed to encode JSON.</error>');
            return self::FAILURE;
        }

        file_put_contents($outputFile, $json . "\n");

        $absolutePath = realpath($outputFile) ?: (getcwd() . \DIRECTORY_SEPARATOR . $outputFile);
        $output->writeln(sprintf('<info>%s generated (%s@%s)</info>', $absolutePath, $name, $version));

        return self::SUCCESS;
    }

    /**
     * Resolves an option value: explicit CLI arg > first non-empty env var fallback.
     */
    private function resolve(InputInterface $input, string $option, string ...$envFallbacks): string
    {
        $value = (string) $input->getOption($option);
        if ($value !== '') {
            return $value;
        }

        foreach ($envFallbacks as $envVar) {
            $env = getenv($envVar);
            if ($env !== false && $env !== '') {
                return $env;
            }
        }

        return '';
    }
}
