<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles CORS and HTTP security headers.
 * Concrete implementation lives in Epic 7.
 */
interface HeaderManagerInterface
{
    /**
     * Add CORS headers to the response based on the request Origin.
     */
    public function addCors(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Validate the presence and value of required security headers.
     * Returns false when validation fails; call getError() for the reason.
     */
    public function validateSecurityHeader(ServerRequestInterface $request): bool;

    /**
     * Add/rotate the security header value in the response.
     */
    public function updateSecurityHeader(ResponseInterface $response): ResponseInterface;

    /** Return the last validation error message. */
    public function getError(): string;
}
