<?php

declare(strict_types=1);

namespace Directive\Http\Response;

use Directive\Http\Response\ResponseEntity;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Centralised PSR-7 response factory.
 *
 * Every response carries the same JSON envelope:
 *   { "message": string, "data": mixed, "errors"?: array, "route": string, "method": string, "status": int }
 *
 * The `errors` key is present only on 400 and 422 responses when the list is non-empty.
 *
 * Instantiated once in the DI container and injected into the Router.
 */
final class HttpResponse
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    // ------------------------------------------------------------------
    // Success
    // ------------------------------------------------------------------

    public function ok(
        ResponseEntity $entity,
        string $route,
        string $method,
    ): ResponseInterface {
        if ($entity->isStream()) {
            return $this->binaryResponse($entity);
        }

        return $this->json(200, 'OK', $entity->getData(), $route, $method);
    }

    // ------------------------------------------------------------------
    // Client errors
    // ------------------------------------------------------------------

    /**
     * @param array<array<string, string>> $errors
     */
    public function badRequest(
        string $route,
        string $method,
        array $errors = [],
    ): ResponseInterface {
        return $this->json(400, 'Bad Request', null, $route, $method, $errors);
    }

    public function unauthorized(string $route, string $method): ResponseInterface
    {
        return $this->json(401, 'Unauthorized', [], $route, $method);
    }

    public function forbidden(string $route, string $method): ResponseInterface
    {
        return $this->json(403, 'Forbidden', [], $route, $method);
    }

    public function notFound(string $route, string $method): ResponseInterface
    {
        return $this->json(404, 'Not Found', [], $route, $method);
    }

    public function methodNotAllowed(string $route, string $method): ResponseInterface
    {
        return $this->json(405, 'Method Not Allowed', [], $route, $method);
    }

    /**
     * @param array<array<string, string>> $errors
     */
    public function unprocessable(
        string $route,
        string $method,
        array $errors = [],
    ): ResponseInterface {
        return $this->json(422, 'Unprocessable Content', null, $route, $method, $errors);
    }

    public function gone(string $route, string $method): ResponseInterface
    {
        return $this->json(410, 'Gone', [], $route, $method);
    }

    public function tooManyRequests(string $route, string $method, string $retryAfter = '60'): ResponseInterface
    {
        return $this->json(429, 'Too Many Requests', [], $route, $method)
            ->withHeader('Retry-After', $retryAfter);
    }

    // ------------------------------------------------------------------
    // Server errors
    // ------------------------------------------------------------------

    public function internalError(string $route, string $method): ResponseInterface
    {
        return $this->json(500, 'Internal Server Error', [], $route, $method);
    }

    public function serviceUnavailable(string $route, string $method, string $retryAfter = '3600', string $message = 'Service Unavailable'): ResponseInterface
    {
        return $this->json(503, $message, [], $route, $method)
            ->withHeader('Retry-After', $retryAfter);
    }

    /**
     * 412 Precondition Failed — used by HttpSecurityMiddleware.
     * No route/method context available at that stage.
     */
    public function preconditionFailed(string $error): ResponseInterface
    {
        return $this->json(412, $error, [], '-', '-');
    }

    /**
     * Empty 200 response — used as a base to attach CORS headers before
     * the actual response is produced.
     */
    public function emptyResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse(200);
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * @param array<array<string, string>> $errors
     */
    private function json(
        int $status,
        string $message,
        mixed $data,
        string $route,
        string $method,
        array $errors = [],
    ): ResponseInterface {
        $envelope = [
            'message' => $message,
            'data'    => $data,
            'route'   => $route,
            'method'  => $method,
            'status'  => $status,
        ];

        if ($errors !== []) {
            $envelope['errors'] = $errors;
        }

        $payload = json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $body = $this->streamFactory->createStream($payload !== false ? $payload : '{}');

        return $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body);
    }

    private function binaryResponse(ResponseEntity $entity): ResponseInterface
    {
        $stream   = $entity->getStream();
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', $entity->getContentType())
            ->withHeader(
                'Content-Disposition',
                'attachment; filename="' . $entity->getFilename() . '"',
            );

        if ($entity->getFilesize() > 0) {
            $response = $response->withHeader('Content-Length', (string) $entity->getFilesize());
        }

        if ($stream !== null) {
            $psr7Stream = $this->streamFactory->createStreamFromResource($stream);
            $response   = $response->withBody($psr7Stream);
        }

        return $response;
    }
}
