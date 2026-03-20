<?php

declare(strict_types=1);

namespace Directive\Web\InterfaceData;

use Directive\Web\Constraints\GenericConstraintInterface;
use Directive\Web\Constraints\NoConstraint;

/**
 * Abstract base for all typed input wrappers.
 *
 * Subclasses implement clean() to sanitize the raw value.
 * The constraint is checked against the cleaned value.
 */
abstract class InterfaceData implements InterfaceDataInterface
{
    private mixed $originalValue = null;

    private mixed $cleanedValue = null;

    private bool $cleaned = false;

    public function __construct(
        private readonly GenericConstraintInterface $constraint = new NoConstraint(),
        private readonly string $errorLabel = '',
    ) {}

    // ------------------------------------------------------------------
    // InterfaceDataInterface
    // ------------------------------------------------------------------

    public function hydrate(mixed $rawData): void
    {
        $this->originalValue = $rawData;
        $this->cleaned       = false;
        $this->cleanedValue  = null;
    }

    public function getOriginalValue(): mixed
    {
        return $this->originalValue;
    }

    final public function getCleanedValue(): mixed
    {
        if (!$this->cleaned) {
            $this->cleanedValue = $this->clean($this->originalValue);
            $this->cleaned      = true;
        }

        return $this->cleanedValue;
    }

    final public function getResultValue(): mixed
    {
        return $this->validate() ? $this->getCleanedValue() : null;
    }

    final public function validate(): bool
    {
        return $this->constraint->checkConstraint($this->getCleanedValue());
    }

    final public function getErrorLabel(): string
    {
        return $this->errorLabel;
    }

    // ------------------------------------------------------------------
    // Hook for subclasses
    // ------------------------------------------------------------------

    /**
     * Sanitize/transform the raw value.
     * The result is cached — called at most once per hydration.
     */
    abstract protected function clean(mixed $raw): mixed;
}
