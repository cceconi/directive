<?php

declare(strict_types=1);

namespace Directive\Service\AppManagement;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Extracts and exposes custom client identification headers.
 * Concrete implementation lives in Epic 8.
 */
interface ClientHeadersInterface
{
    /** Parse the request and store client header values. */
    public function load(ServerRequestInterface $request): void;

    /** Return the stored value for a header, or null when absent. */
    public function get(string $headerName): ?string;
}
