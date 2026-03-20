<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Web\RequestEntity;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contract for all Policy classes.
 *
 * A Policy validates and cleans the incoming request data, producing a RequestEntity.
 * Epic 4: expand with addScalar/addFile/addObject/addArray helpers.
 */
interface PolicyInterface
{
    /**
     * Set the raw PSR-7 request for this policy to process.
     */
    public function setRequest(ServerRequestInterface $request): void;

    /**
     * Validate & clean input, hydrate the RequestEntity.
     * Returns the entity (even if there are errors — caller checks hasErrors()).
     */
    public function getRequestEntity(): RequestEntity;

    /** @return array<array<string, string>> */
    public function getErrors(): array;

    public function hasErrors(): bool;
}
