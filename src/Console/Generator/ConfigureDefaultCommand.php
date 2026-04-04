<?php

declare(strict_types=1);

namespace Directive\Console\Generator;

use Directive\Cli\DirectiveCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'generator:configure-default',
    description: 'Configure the default namespace and source directory for code generation (writes directive-dev.json).',
)]
final class ConfigureDefaultCommand extends DirectiveCommand
{
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $cwd = (string) getcwd();

        // Priority 1 — existing directive-dev.json (reconfiguration case)
        $suggestedNamespace = null;
        $suggestedSrc       = null;

        $devJson = $cwd . '/directive-dev.json';
        if (is_file($devJson)) {
            $existing = json_decode((string) file_get_contents($devJson), true);
            if (is_array($existing)) {
                $suggestedNamespace = is_string($existing['namespace'] ?? null) ? $existing['namespace'] : null;
                $suggestedSrc       = is_string($existing['src'] ?? null) ? $existing['src'] : null;
            }
        }

        // Priority 2 — first PSR-4 mapping from composer.json (first-time setup)
        if ($suggestedNamespace === null || $suggestedSrc === null) {
            $composerJson = $cwd . '/composer.json';
            if (is_file($composerJson)) {
                $composer = json_decode((string) file_get_contents($composerJson), true);
                if (is_array($composer)) {
                    $psr4 = $composer['autoload']['psr-4'] ?? [];
                    if (is_array($psr4) && $psr4 !== []) {
                        /** @var array<string, string> $psr4 */
                        $suggestedNamespace ??= rtrim((string) array_key_first($psr4), '\\');
                        $suggestedSrc       ??= rtrim((string) reset($psr4), '/');
                    }
                }
            }
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $namespaceQuestion = new Question(
            sprintf(
                '<info>Root namespace</info>%s: ',
                $suggestedNamespace !== null ? " [<comment>{$suggestedNamespace}</comment>]" : '',
            ),
            $suggestedNamespace,
        );

        $srcQuestion = new Question(
            sprintf(
                '<info>Source directory</info>%s: ',
                $suggestedSrc !== null ? " [<comment>{$suggestedSrc}</comment>]" : '',
            ),
            $suggestedSrc,
        );

        $namespace = (string) $helper->ask($input, $output, $namespaceQuestion);
        $src       = (string) $helper->ask($input, $output, $srcQuestion);

        if ($namespace === '' || $src === '') {
            $output->writeln('<error>Namespace and source directory are required.</error>');

            return self::FAILURE;
        }

        file_put_contents(
            $devJson,
            json_encode(['namespace' => $namespace, 'src' => $src], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
        );

        $output->writeln(sprintf('<info>Written:</info> %s', $devJson));
        $output->writeln('<comment>Tip: add directive-dev.json to .gitignore if this is a project-local file.</comment>');

        return self::SUCCESS;
    }
}
