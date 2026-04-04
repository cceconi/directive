<?php

declare(strict_types=1);

namespace Directive\Cli\Validator;

use Directive\Input\InterfaceDataInterface;

/**
 * DTO carrying the validated, hydrated input fields for a CLI command.
 *
 * The CLI equivalent of RequestEntity — populated by AbstractCommandInputValidator
 * after a successful validation run.
 */
final class CommandInputEntity
{
    /** @var array<string, InterfaceDataInterface> */
    private array $fields = [];

    // ------------------------------------------------------------------
    // Called by AbstractCommandInputValidator
    // ------------------------------------------------------------------

    /**
     * Register a validated InterfaceData field by name.
     */
    public function registerField(string $name, InterfaceDataInterface $field): void
    {
        $this->fields[$name] = $field;
    }

    // ------------------------------------------------------------------
    // Accessors
    // ------------------------------------------------------------------

    public function getField(string $name): ?InterfaceDataInterface
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Returns the cleaned (typed) value for $name, or null when the field is absent.
     *
     * Shorthand for `getField($name)?->getCleanedValue()` — mirrors the
     * convenience accessor available on RequestEntity.
     */
    public function get(string $name): mixed
    {
        return $this->getField($name)?->getCleanedValue();
    }

    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Returns all cleaned (typed) values indexed by field name.
     *
     * @return array<string, mixed>
     */
    public function getCleanedValues(): array
    {
        $result = [];
        foreach ($this->fields as $name => $field) {
            $result[$name] = $field->getCleanedValue();
        }

        return $result;
    }
}
