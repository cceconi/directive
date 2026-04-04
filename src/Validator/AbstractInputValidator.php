<?php

declare(strict_types=1);

namespace Directive\Validator;

use Directive\Input\InterfaceDataInterface;

/**
 * Shared base for all input validators (HTTP and CLI).
 *
 * Responsibility:
 *   - Maintain the registry of declared input fields ($scalars, $files, $objects, $arrays).
 *   - Collect structured validation errors.
 *   - Provide helper methods (scalar / file / object / array) for subclasses to register fields.
 *   - Define the single abstract hook register() that concrete validators implement.
 *
 * Lifecycle:
 *   1. Specialised subclass calls reset() (or the subclass's own setter does it).
 *   2. Subclass-specific "get entity" method calls register() once per validation run.
 *   3. register() invokes the helper methods.
 *   4. Subclass performs hydration/validation and may call addError().
 *   5. Caller reads hasErrors() / getErrors().
 */
abstract class AbstractInputValidator
{
    /** @var array<string, array{field: InterfaceDataInterface, required: bool}> */
    private array $scalars = [];

    /** @var array<string, array{field: InterfaceDataInterface, required: bool}> */
    private array $files = [];

    /** @var array<string, array{field: InterfaceDataInterface, required: bool}> */
    private array $objects = [];

    /** @var array<string, array{field: InterfaceDataInterface, required: bool}> */
    private array $arrays = [];

    /** @var array<array<string, string>> */
    private array $errors = [];

    // ------------------------------------------------------------------
    // Error management — public read / protected write
    // ------------------------------------------------------------------

    /** @return array<array<string, string>> */
    final public function getErrors(): array
    {
        return $this->errors;
    }

    final public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Push a structured error entry into the shared error list.
     *
     * @param 'invalid'|'missing' $type
     */
    final protected function addError(
        string $property,
        string $message,
        string $type = 'invalid',
    ): void {
        $this->errors[] = [
            'property' => $property,
            'message'  => $message,
            'type'     => $type,
        ];
    }

    // ------------------------------------------------------------------
    // State reset
    // ------------------------------------------------------------------

    /**
     * Clear all registered fields and accumulated errors.
     * Must be called before each validation run.
     */
    final public function reset(): void
    {
        $this->scalars = [];
        $this->files   = [];
        $this->objects = [];
        $this->arrays  = [];
        $this->errors  = [];
    }

    // ------------------------------------------------------------------
    // Registration helpers (call inside register())
    // ------------------------------------------------------------------

    /**
     * Register a scalar input (string, numeric, boolean, date, …).
     */
    final protected function scalar(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        $this->guardDuplicate($name);
        $this->scalars[$name] = ['field' => $field, 'required' => $required];
    }

    /**
     * Register a file-upload input.
     *
     * In CLI contexts this method SHOULD be overridden to throw \LogicException.
     */
    protected function file(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        $this->guardDuplicate($name);
        $this->files[$name] = ['field' => $field, 'required' => $required];
    }

    /**
     * Register a nested-object input (e.g. a JSON sub-object decoded as array).
     */
    final protected function object(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        $this->guardDuplicate($name);
        $this->objects[$name] = ['field' => $field, 'required' => $required];
    }

    /**
     * Register an array input.
     */
    final protected function array(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        $this->guardDuplicate($name);
        $this->arrays[$name] = ['field' => $field, 'required' => $required];
    }

    // ------------------------------------------------------------------
    // Abstract registration hook
    // ------------------------------------------------------------------

    /**
     * Declare all expected input fields by calling scalar() / file() / object() / array().
     * Called once at the beginning of each validation run.
     */
    abstract protected function register(): void;

    // ------------------------------------------------------------------
    // Protected accessors for specialised subclasses
    // ------------------------------------------------------------------

    /** @return array<string, array{field: InterfaceDataInterface, required: bool}> */
    final protected function getRegisteredScalars(): array
    {
        return $this->scalars;
    }

    /** @return array<string, array{field: InterfaceDataInterface, required: bool}> */
    final protected function getRegisteredFiles(): array
    {
        return $this->files;
    }

    /** @return array<string, array{field: InterfaceDataInterface, required: bool}> */
    final protected function getRegisteredObjects(): array
    {
        return $this->objects;
    }

    /** @return array<string, array{field: InterfaceDataInterface, required: bool}> */
    final protected function getRegisteredArrays(): array
    {
        return $this->arrays;
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function guardDuplicate(string $name): void
    {
        $all = [
            ...array_keys($this->scalars),
            ...array_keys($this->files),
            ...array_keys($this->objects),
            ...array_keys($this->arrays),
        ];

        if (in_array($name, $all, true)) {
            throw new \InvalidArgumentException(
                sprintf('Field "%s" is already registered in this validator.', $name),
            );
        }
    }
}
