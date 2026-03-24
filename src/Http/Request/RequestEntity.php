<?php

declare(strict_types=1);

namespace Directive\Http\Request;

use Directive\Http\Input\InterfaceDataInterface;

/**
 * Base DTO for incoming validated data.
 *
 * Policy hydrates each validated field via registerField().
 * Subclasses can additionally declare typed setters that call registerField()
 * for IDE / static-analysis support.
 *
 * Usage example in a concrete Policy:
 *   $entity->setEmail(new EmailAddress(...))
 *
 * Usage in an Api handler:
 *   $email = $this->requestEntity->getCleanedValues()['email'] ?? null;
 *   // or via typed getter in the concrete subclass
 */
abstract class RequestEntity
{
    /** @var array<string, InterfaceDataInterface> */
    private array $fields = [];

    // ------------------------------------------------------------------
    // Called by Policy (and concrete subclass setters)
    // ------------------------------------------------------------------

    /**
     * Register a validated InterfaceData field by name.
     * Called automatically by Policy after validation succeeds.
     */
    final public function registerField(string $name, InterfaceDataInterface $field): void
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

    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Returns all cleaned values indexed by field name.
     * Only fields that passed validation are present (Policy filters errors out).
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

    /**
     * Returns all original (raw) values indexed by field name.
     *
     * @return array<string, mixed>
     */
    public function getOriginalValues(): array
    {
        $result = [];
        foreach ($this->fields as $name => $field) {
            $result[$name] = $field->getOriginalValue();
        }
        return $result;
    }

    /**
     * Convenience: get a single cleaned value.
     */
    public function get(string $name): mixed
    {
        $field = $this->fields[$name] ?? null;

        return $field !== null ? $field->getCleanedValue() : null;
    }

    /**
     * @return array<string, InterfaceDataInterface>
     */
    public function all(): array
    {
        return $this->fields;
    }
}
