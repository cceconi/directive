<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\PolicyException;
use Directive\Web\InterfaceData\InterfaceDataInterface;
use Directive\Web\RequestEntity;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract base for all input validation policies.
 *
 * Lifecycle:
 *   1. setRequest($request)
 *   2. registerScalars() / registerFiles() / registerObjects() / registerArrays()
 *      → each calls addScalar() / addFile() / addObject() / addArray()
 *   3. getRequestEntity() → hydrates, validates, collects errors
 *
 * Concrete subclasses implement the four register*() methods.
 */
abstract class Policy implements PolicyInterface
{
    private ServerRequestInterface $request;

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
    // PolicyInterface
    // ------------------------------------------------------------------

    final public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
        $this->errors  = [];
        $this->scalars = [];
        $this->files   = [];
        $this->objects = [];
        $this->arrays  = [];
    }

    final public function getRequestEntity(): RequestEntity
    {
        $this->registerScalars();
        $this->registerFiles();
        $this->registerObjects();
        $this->registerArrays();

        $body    = $this->request->getParsedBody() ?? [];
        $uploads = $this->request->getUploadedFiles();

        /** @var array<string, mixed> $bodyArray */
        $bodyArray = is_array($body) ? $body : [];

        $entity = $this->createRequestEntity();

        $this->hydrateAndValidate($this->scalars, $bodyArray, $entity);
        $this->hydrateAndValidate($this->objects, $bodyArray, $entity);
        $this->hydrateAndValidate($this->arrays,  $bodyArray, $entity);

        // File inputs
        foreach ($this->files as $name => $entry) {
            $raw = $uploads[$name] ?? null;
            if ($raw === null) {
                if ($entry['required']) {
                    $this->errors[] = [
                        'property' => $name,
                        'message'  => sprintf('Field "%s" is required.', $name),
                        'type'     => 'missing',
                    ];
                }
                continue;
            }
            $entry['field']->hydrate($raw);
            if (!$entry['field']->validate()) {
                $this->errors[] = [
                    'property' => $name,
                    'message'  => $entry['field']->getErrorLabel(),
                    'type'     => 'invalid',
                ];
            } else {
                $this->injectIntoEntity($entity, $name, $entry['field']);
            }
        }

        return $entity;
    }

    /** @return array<array<string, string>> */
    final public function getErrors(): array
    {
        return $this->errors;
    }

    final public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    // ------------------------------------------------------------------
    // Registration helpers
    // ------------------------------------------------------------------

    /**
     * Register a scalar input field (string, numeric, boolean, date…).
     */
    final protected function addScalar(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        $this->guardDuplicate($name);
        $this->scalars[$name] = ['field' => $field, 'required' => $required];
    }

    /**
     * Shorthand for addScalar with no constraint.
     */
    final protected function addEasyScalar(string $name, bool $required = false): void
    {
        $this->addScalar($name, new \Directive\Web\InterfaceData\SimpleString(), $required);
    }

    /**
     * Register a file upload field.
     */
    final protected function addFile(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        $this->guardDuplicate($name);
        $this->files[$name] = ['field' => $field, 'required' => $required];
    }

    /**
     * Register a nested object (JSON sub-object decoded as array).
     */
    final protected function addObject(
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
    final protected function addArray(
        string $name,
        InterfaceDataInterface $field,
        bool $required = false,
    ): void {
        $this->guardDuplicate($name);
        $this->arrays[$name] = ['field' => $field, 'required' => $required];
    }

    // ------------------------------------------------------------------
    // Abstract registration hooks
    // ------------------------------------------------------------------

    abstract protected function registerScalars(): void;
    abstract protected function registerFiles(): void;
    abstract protected function registerObjects(): void;
    abstract protected function registerArrays(): void;

    // ------------------------------------------------------------------
    // Hooks for subclasses
    // ------------------------------------------------------------------

    /**
     * Override to return a custom RequestEntity subclass.
     */
    protected function createRequestEntity(): RequestEntity
    {
        return new class extends RequestEntity {};
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * @param array<string, array{field: InterfaceDataInterface, required: bool}> $fields
     * @param array<string, mixed> $data
     */
    private function hydrateAndValidate(array $fields, array $data, RequestEntity $entity): void
    {
        foreach ($fields as $name => $entry) {
            $raw = $data[$name] ?? null;

            if ($raw === null) {
                if ($entry['required']) {
                    $this->errors[] = [
                        'property' => $name,
                        'message'  => sprintf('Field "%s" is required.', $name),
                        'type'     => 'missing',
                    ];
                }
                continue;
            }

            $entry['field']->hydrate($raw);

            if (!$entry['field']->validate()) {
                $this->errors[] = [
                    'property' => $name,
                    'message'  => $entry['field']->getErrorLabel() !== ''
                        ? $entry['field']->getErrorLabel()
                        : sprintf('Field "%s" is invalid.', $name),
                    'type'     => 'invalid',
                ];
            } else {
                $this->injectIntoEntity($entity, $name, $entry['field']);
            }
        }
    }

    private function injectIntoEntity(RequestEntity $entity, string $name, InterfaceDataInterface $field): void
    {
        // Always register in the universal field registry.
        $entity->registerField($name, $field);

        // Additionally call a typed setter if the concrete subclass defines one.
        // Convention: setFieldName(InterfaceDataInterface)
        $setter = 'set' . ucfirst($name);
        if (method_exists($entity, $setter)) {
            $entity->$setter($field);
        }
    }

    private function guardDuplicate(string $name): void
    {
        $all = array_merge(
            array_keys($this->scalars),
            array_keys($this->files),
            array_keys($this->objects),
            array_keys($this->arrays),
        );

        if (in_array($name, $all, true)) {
            throw new PolicyException(sprintf('Field "%s" is already registered in this policy.', $name));
        }
    }
}
