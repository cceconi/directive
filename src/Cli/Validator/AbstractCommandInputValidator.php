<?php

declare(strict_types=1);

namespace Directive\Cli\Validator;

use Directive\Input\InterfaceDataInterface;
use Directive\Validator\AbstractInputValidator;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Abstract base for CLI command input validators.
 *
 * Extends AbstractInputValidator and adapts the validation lifecycle for
 * Symfony Console commands: reads arguments and options from InputInterface
 * instead of a PSR-7 request, and produces a CommandInputEntity.
 *
 * Lifecycle:
 *   1. setInput($input)                — stores input, resets state
 *   2. register()                      → calls scalar() / object() / array()
 *   3. getCommandInputEntity()         → hydrates, validates, collects errors
 *
 * Note: file() is not available in CLI context — it throws \LogicException.
 */
abstract class AbstractCommandInputValidator extends AbstractInputValidator
{
    private InputInterface $input;

    // ------------------------------------------------------------------
    // Lifecycle
    // ------------------------------------------------------------------

    final public function setInput(InputInterface $input): void
    {
        $this->input = $input;
        $this->reset();
    }

    final public function getCommandInputEntity(): CommandInputEntity
    {
        $this->register();

        $entity = new CommandInputEntity();

        // Arguments and options share the same hydration loop; Symfony Console
        // does not distinguish them at the InputInterface level (getArgument /
        // getOption both return mixed). We try arguments first, fall back to options.
        $fields = [
            ...$this->getRegisteredScalars(),
            ...$this->getRegisteredObjects(),
            ...$this->getRegisteredArrays(),
        ];

        foreach ($fields as $name => $entry) {
            $raw = $this->resolveInputValue($name);

            if ($raw === null) {
                if ($entry['required']) {
                    $this->addError($name, sprintf('Argument/option "%s" is required.', $name), 'missing');
                }
                continue;
            }

            $entry['field']->hydrate($raw);

            if (!$entry['field']->validate()) {
                $label = $entry['field']->getErrorLabel();
                $this->addError(
                    $name,
                    $label !== '' ? $label : sprintf('Argument/option "%s" is invalid.', $name),
                );
            } else {
                $entity->registerField($name, $entry['field']);
            }
        }

        return $entity;
    }

    // ------------------------------------------------------------------
    // Disabled: file() is not supported in CLI context
    // ------------------------------------------------------------------

    /**
     * Disabled in CLI context — always throws.
     *
     * File uploads are an HTTP-only concept. Calling this method from a CLI
     * validator is a programming error: the method is intentionally sealed
     * (final) so that no subclass can silently re-enable it.
     *
     * If you need to read a file path from the command line, register it as a
     * scalar() argument/option and resolve the path in your handle()/tick()
     * implementation.
     *
     * @throws \LogicException Always — file uploads are not available in CLI.
     */
    final protected function file(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        throw new \LogicException('file() is not supported in CLI context');
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    private function resolveInputValue(string $name): mixed
    {
        // Try as argument first, then as option.
        if ($this->input->hasArgument($name)) {
            $value = $this->input->getArgument($name);
            if ($value !== null) {
                return $value;
            }
        }

        if ($this->input->hasOption($name)) {
            return $this->input->getOption($name);
        }

        return null;
    }
}
