<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Http\Middleware\HttpConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Compresses the response body with gzip or deflate when:
 *   - Configuration key "env.response.compress" is truthy
 *   - The client sends a matching Accept-Encoding header
 */
final class CompressResponseMiddleware extends AbstractMiddleware
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        /** @var HttpConfigInterface $config */
        $config = $this->container->get(HttpConfigInterface::class);

        if (!$config->isCompressionEnabled()) {
            return $response;
        }

        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');

        if ($acceptEncoding === '') {
            return $response;
        }

        $requested = array_map(
            static fn(string $e) => trim(strtolower($e)),
            explode(',', $acceptEncoding),
        );

        $supported = [
            'gzip'    => ZLIB_ENCODING_GZIP,
            'deflate' => ZLIB_ENCODING_DEFLATE,
        ];

        $chosen = null;
        foreach (array_keys($supported) as $encoding) {
            if (in_array($encoding, $requested, strict: true)) {
                $chosen = $encoding;
                break;
            }
        }

        if ($chosen === null) {
            return $response;
        }

        $body = (string) $response->getBody();

        $compressed = gzcompress($body, 6, $supported[$chosen]);

        if ($compressed === false) {
            return $response;
        }

        /** @var StreamFactoryInterface $streamFactory */
        $streamFactory = $this->container->get(StreamFactoryInterface::class);

        $stream = $streamFactory->createStream($compressed);

        return $response
            ->withHeader('Content-Encoding', $chosen)
            ->withBody($stream);
    }
}
