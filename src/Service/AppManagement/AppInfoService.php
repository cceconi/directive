<?php

declare(strict_types=1);

namespace Directive\Service\AppManagement;

use Directive\Service\Configuration\Configuration;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Built-in app-info service.
 *
 * Exposes two levels of information following the Spring Boot Actuator convention:
 *
 *  - GET /info         — always public: name + version only.
 *  - GET /info/details — full build info; protected by a static management token
 *                        (Bearer realm="management") distinct from the app auth realm.
 *                        Disabled (503) when MANAGEMENT_TOKEN is not configured.
 */
final class AppInfoService
{
    public function __construct(
        private readonly AppInfoInterface $appInfo,
        private readonly Psr17Factory $psr17Factory,
        private readonly Configuration $config,
    ) {}

    // ------------------------------------------------------------------
    // Route handlers
    // ------------------------------------------------------------------

    /** Handle GET /info — always public. */
    public function resolvePublic(ResponseInterface $response): ResponseInterface
    {
        return $this->buildJsonResponse($response, $this->publicInfo());
    }

    /**
     * Handle GET /info/details — full build info.
     *
     * Authentication flow (management realm, Bearer token):
     *  - MANAGEMENT_TOKEN not configured → 503 Service Unavailable (endpoint disabled)
     *  - Authorization header missing    → 401 + WWW-Authenticate: Bearer realm="management"
     *  - Token mismatch                  → 403 Forbidden
     *  - Token matches                   → 200 with full info
     */
    public function resolveDetails(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $configured = (string) $this->config->get('MANAGEMENT_TOKEN');

        if ($configured === '') {
            return $this->buildErrorResponse($response, 503, 'management endpoint not configured');
        }

        $header = $request->getHeaderLine('Authorization');

        if ($header === '') {
            return $this->buildErrorResponse($response, 401, 'authentication required')
                ->withHeader('WWW-Authenticate', 'Bearer realm="management"');
        }

        $provided = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';

        if (!hash_equals($configured, $provided)) {
            return $this->buildErrorResponse($response, 403, 'invalid management token');
        }

        return $this->buildJsonResponse($response, $this->fullInfo());
    }

    // ------------------------------------------------------------------
    // Data accessors (useful for tests and other services)
    // ------------------------------------------------------------------

    /** @return array<string, string> name + version. */
    public function publicInfo(): array
    {
        return [
            'name'    => $this->appInfo->getName(),
            'version' => $this->appInfo->getVersion(),
        ];
    }

    /** @return array<string, string> Full build details. */
    public function fullInfo(): array
    {
        return [
            'name'        => $this->appInfo->getName(),
            'version'     => $this->appInfo->getVersion(),
            'commitId'    => $this->appInfo->getCommitId(),
            'branch'      => $this->appInfo->getBranch(),
            'tag'         => $this->appInfo->getTag(),
            'buildNumber' => $this->appInfo->getBuildNumber(),
            'builtAt'     => $this->appInfo->getBuiltAt(),
            'builtBy'     => $this->appInfo->getBuiltBy(),
        ];
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /** @param array<string, string> $data */
    private function buildJsonResponse(ResponseInterface $response, array $data): ResponseInterface
    {
        $body   = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stream = $this->psr17Factory->createStream($body !== false ? $body : '{}');

        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }

    private function buildErrorResponse(ResponseInterface $response, int $status, string $message): ResponseInterface
    {
        $body   = json_encode(['error' => $message], JSON_UNESCAPED_SLASHES);
        $stream = $this->psr17Factory->createStream($body !== false ? $body : '{}');

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($stream);
    }
}
