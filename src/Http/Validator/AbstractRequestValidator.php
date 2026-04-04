<?php

declare(strict_types=1);

namespace Directive\Http\Validator;

use Directive\Input\InterfaceDataInterface;
use Directive\Http\Request\RequestEntity;
use Directive\Validator\AbstractInputValidator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract base for all HTTP request validators.
 *
 * Extends AbstractInputValidator which owns the field registry and error
 * collection. This class adds HTTP-specific concerns: reading from a
 * ServerRequestInterface, hydrating a RequestEntity, and handling file uploads.
 *
 * Lifecycle:
 *   1. setRequest($request)          — stores request, resets state
 *   2. register()                    → calls scalar() / file() / object() / array()
 *   3. getRequestEntity()            → hydrates, validates, collects errors
 */
abstract class AbstractRequestValidator extends AbstractInputValidator implements RequestValidatorInterface
{
    private ServerRequestInterface $request;

    // ------------------------------------------------------------------
    // RequestValidatorInterface
    // ------------------------------------------------------------------

    final public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
        $this->reset();
    }

    final public function getRequestEntity(): RequestEntity
    {
        $this->register();

        $body    = $this->request->getParsedBody() ?? [];
        $uploads = $this->request->getUploadedFiles();

        /** @var array<string, mixed> $bodyArray */
        $bodyArray = is_array($body) ? $body : [];

        $entity = $this->createRequestEntity();

        $this->hydrateAndValidate($this->getRegisteredScalars(), $bodyArray, $entity);
        $this->hydrateAndValidate($this->getRegisteredObjects(), $bodyArray, $entity);
        $this->hydrateAndValidate($this->getRegisteredArrays(), $bodyArray, $entity);

        // File inputs — handled separately because source is uploadedFiles, not parsed body.
        foreach ($this->getRegisteredFiles() as $name => $entry) {
            $raw = $uploads[$name] ?? null;
            if ($raw === null) {
                if ($entry['required']) {
                    $this->addError($name, sprintf('Field "%s" is required.', $name), 'missing');
                }
                continue;
            }
            $entry['field']->hydrate($raw);
            if (!$entry['field']->validate()) {
                $this->addError($name, $entry['field']->getErrorLabel());
            } else {
                $this->injectIntoEntity($entity, $name, $entry['field']);
            }
        }

        return $entity;
    }

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

    /**
     * Provides subclasses read access to the current request.
     * Only valid after setRequest() has been called (i.e., inside register() or later).
     */
    final protected function getRequest(): ServerRequestInterface
    {
        return $this->request;
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
                    $this->addError($name, sprintf('Field "%s" is required.', $name), 'missing');
                }
                continue;
            }

            $entry['field']->hydrate($raw);

            if (!$entry['field']->validate()) {
                $label = $entry['field']->getErrorLabel();
                $this->addError(
                    $name,
                    $label !== '' ? $label : sprintf('Field "%s" is invalid.', $name),
                );
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
}
