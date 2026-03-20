<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Manages reading cookies from the request and writing them to the response.
 * Concrete implementation uses dflydev/fig-cookies v3.
 */
interface CookiesManagerInterface
{
    /** Extract and store cookies from the incoming request. */
    public function storeFromRequest(ServerRequestInterface $request): void;

    /** Append queued Set-Cookie headers to the outgoing response. */
    public function setResponseCookies(ResponseInterface $response): ResponseInterface;

    /** Queue a generic response cookie. */
    public function addResponseCookie(
        string $name,
        string $value,
        ?int $maxAge = null,
        ?string $domain = null,
    ): void;

    /** Queue the access_token response cookie. */
    public function addResponseAccessTokenCookie(
        string $value,
        ?int $maxAge = null,
        ?string $domain = null,
    ): void;
}
