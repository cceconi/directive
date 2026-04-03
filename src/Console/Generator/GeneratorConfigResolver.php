<?php

declare(strict_types=1);

namespace Directive\Console\Generator;

/**
 * Resolves the default namespace + src directory for code generation.
 *
 * Resolution order:
 *   1. directive-dev.json in the current working directory
 *   2. First PSR-4 autoload mapping in composer.json in the current working directory
 *   3. null — caller should prompt interactively
 */
final class GeneratorConfigResolver
{
    private int $lastPsr4Count = 0;

    /**
     * @return array{namespace: string, src: string}|null
     */
    public function resolve(): ?array
    {
        $cwd = (string) getcwd();

        // Level 1 — directive-dev.json
        $devJson = $cwd . '/directive-dev.json';
        if (is_file($devJson)) {
            $data = json_decode((string) file_get_contents($devJson), true);
            if (is_array($data)
                && isset($data['namespace'], $data['src'])
                && is_string($data['namespace'])
                && is_string($data['src'])
            ) {
                return ['namespace' => $data['namespace'], 'src' => $data['src']];
            }
        }

        // Level 2 — first PSR-4 mapping in composer.json
        $composerJson = $cwd . '/composer.json';
        if (is_file($composerJson)) {
            $composer = json_decode((string) file_get_contents($composerJson), true);
            if (is_array($composer)) {
                $psr4 = $composer['autoload']['psr-4'] ?? [];
                if (is_array($psr4) && $psr4 !== []) {
                    /** @var array<string, string> $psr4 */
                    $this->lastPsr4Count = count($psr4);
                    $namespace = (string) array_key_first($psr4);
                    $src = (string) reset($psr4);

                    return ['namespace' => rtrim($namespace, '\\'), 'src' => rtrim($src, '/')];
                }
            }
        }

        // Level 3 — prompt (caller responsibility)
        return null;
    }

    /**
     * Returns true if the last resolve() call found multiple PSR-4 mappings
     * in composer.json and implicitly chose the first one.
     * Use this to emit a warning to the user.
     */
    public function hasMultiplePsr4Mappings(): bool
    {
        return $this->lastPsr4Count > 1;
    }
}
