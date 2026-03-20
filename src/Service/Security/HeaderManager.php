<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Directive\Service\Configuration\ConfigurationInterface;
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

    /**
     * Optional headers driven by configuration keys.
     *
     * @var array<string, string>
     */
    private const CONFIG_HEADERS = [
        'env.security.http.header.sts'  => 'Strict-Transport-Security',
        'env.security.http.header.sfo'  => 'X-Frame-Options',
        'env.security.http.header.xxp'  => 'X-XSS-Protection',
        'env.security.http.header.xcto' => 'X-Content-Type-Options',
        'env.security.http.header.rpo'  => 'Referrer-Policy',
        'env.security.http.header.csp'  => 'Content-Security-Policy',
        'env.security.http.header.ect'  => 'Expect-CT',
        'env.security.http.header.fpo'  => 'Feature-Policy',
    ];

    private string $lastError = '';

    public function __construct(
        private readonly ConfigurationInterface $config,
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
        if ($origin === (string) $this->config->get('env.url', '')) {
            return $response;
        }

        /** @var array<string> $allowedOrigins */
        $allowedOrigins = (array) $this->config->get('env.security.cors', []);

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
        $secure = (bool) $this->config->get('env.security.http.secure', false);

        if ($scheme === 'https' || !$secure) {
            return true;
        }

        // HTTP request but secure mode is on — check relaxed hosts.
        $host     = $request->getUri()->getHost();

        /** @var array<string> $relaxed */
        $relaxed  = (array) $this->config->get('env.security.http.relaxed', []);

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

        // Allow configuration to override.
        foreach (self::CONFIG_HEADERS as $configKey => $header) {
            $value = $this->config->get($configKey);
            if ($value !== null) {
                $response = $response->withHeader($header, (string) $value);
            }
        }

        return $response;
    }

    public function getError(): string
    {
        return $this->lastError;
    }
}
