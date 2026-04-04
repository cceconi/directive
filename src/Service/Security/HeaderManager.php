<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles CORS headers and HTTP security header enforcement.
 */
final class HeaderManager implements HeaderManagerInterface
{
    /**
     * Default security headers (sent unless overridden by configuration).
     */
    private const DEFAULT_HEADERS = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        'X-Frame-Options'            => 'DENY',
        'X-XSS-Protection'           => '1',
        'X-Content-Type-Options'     => 'nosniff',
        'Referrer-Policy'            => 'same-origin',
    ];

    private string $lastError = '';

    public function __construct(
        private readonly SecurityConfigInterface $config,
    ) {}

    // ------------------------------------------------------------------
    // HeaderManagerInterface
    // ------------------------------------------------------------------

    public function addCors(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return $response;
        }

        // Own origin — no CORS headers needed.
        if ($origin === $this->config->getAppUrl()) {
            return $response;
        }

        $allowedOrigins = $this->config->getCorsAllowedOrigins();

        if (!in_array($origin, $allowedOrigins, strict: true)) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Origin', $origin);
    }

    public function validateSecurityHeader(ServerRequestInterface $request): bool
    {
        $scheme = $request->getUri()->getScheme();
        $secure = $this->config->isHttpSecure();

        if ($scheme === 'https' || !$secure) {
            return true;
        }

        // HTTP request but secure mode is on — check relaxed hosts.
        $host    = $request->getUri()->getHost();
        $relaxed = $this->config->getHttpRelaxedHosts();

        if (!in_array($host, $relaxed, strict: true)) {
            $this->lastError = sprintf(
                'Insecure request over %s denied by configuration.',
                strtoupper($scheme),
            );
            return false;
        }

        return true;
    }

    public function updateSecurityHeader(ResponseInterface $response): ResponseInterface
    {
        // Start with defaults.
        foreach (self::DEFAULT_HEADERS as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        // Allow SecurityConfigInterface overrides to replace defaults.
        foreach ($this->config->getSecurityHeaderOverrides() as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response;
    }

    public function getError(): string
    {
        return $this->lastError;
    }
}
