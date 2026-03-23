<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\UnprocessableException;
use Directive\Service\Business\ErrorInterface;
use Directive\Web\RequestEntity;
use Directive\Web\ResponseEntity;
use Psr\Container\ContainerInterface;

/**
 * Base class for all API handlers.
 *
 * Lifecycle called by Router:
 *   run() → preControl() → [check $businessError] → compute() → [check $businessError]
 *
 * Subclasses implement compute() and optionally preControl().
 * Use $this->businessError->add(...) to signal business rule violations.
 */
abstract class AbstractApi implements ApiInterface
{
    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly ResponseEntity $responseEntity,
        protected readonly RequestEntity $requestEntity,
        protected readonly ErrorInterface $businessError,
    ) {
    }

    // ------------------------------------------------------------------
    // ApiInterface
    // ------------------------------------------------------------------

    final public function run(): void
    {
        $this->preControl();

        if ($this->businessError->hasErrors()) {
            throw new UnprocessableException('Business rule violation.')
                ->withErrors($this->businessError->getErrors());
        }

        $this->compute();

        if ($this->businessError->hasErrors()) {
            throw new UnprocessableException('Business rule violation.')
                ->withErrors($this->businessError->getErrors());
        }
    }

    final public function getResponseEntity(): ResponseEntity
    {
        return $this->responseEntity;
    }

    // ------------------------------------------------------------------
    // Hooks
    // ------------------------------------------------------------------

    /**
     * Optional pre-check before compute().
     * Use $this->businessError->add() to halt execution.
     */
    protected function preControl(): void {}

    /**
     * Implement your business logic here.
     * Read from $this->requestEntity, write to $this->responseEntity.
     */
    abstract protected function compute(): void;
}
