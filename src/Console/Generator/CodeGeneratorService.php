<?php

declare(strict_types=1);

namespace Directive\Console\Generator;

final class CodeGeneratorService
{
    private readonly string $templateDir;

    public function __construct()
    {
        $this->templateDir = __DIR__ . '/templates';
    }

    /**
     * Renders a template and writes the output file.
     *
     * @param array<string, string> $options Additional placeholders (e.g. ['input' => 'Command', 'parent' => 'EntityNotFoundException'])
     */
    public function generate(
        string $business,
        string $name,
        string $targetDir,
        string $namespace,
        string $type,
        array $options = [],
    ): void {
        $templateFile = $this->templateDir . '/' . $type;

        if (!is_file($templateFile)) {
            throw new \RuntimeException('Template not found: ' . $templateFile);
        }

        $raw = file_get_contents($templateFile);
        if ($raw === false) {
            throw new \RuntimeException('Cannot read template: ' . $templateFile);
        }

        $replacements = [
            '{{namespace}}' => $namespace,
            '{{business}}'  => $business,
            '{{name}}'      => $name,
        ];

        foreach ($options as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
        }

        $content = str_replace(array_keys($replacements), array_values($replacements), $raw);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $classType = ClassType::from($type);
        $outputFile = $targetDir . '/' . $classType->outputFilename($name);

        if (!is_file($outputFile)) {
            file_put_contents($outputFile, $content);
        }
    }
}
