<?php

declare(strict_types=1);

namespace Directive\Http\Response;

use JsonSerializable;

/**
 * Default DTO for outgoing data.
 *
 * Dual-mode:
 *   - JSON  : setData(array) or setJson(string)
 *   - Stream: setStream() + setFilename() + setContentType() + setFilesize()
 *
 * Epic 5: full stream/binary support.
 */
class ResponseEntity implements ResponseEntityInterface, JsonSerializable
{
    /** @var array<mixed> */
    private array $data = [];

    private bool $stream = false;

    private string $filename = '';

    private string $contentType = 'application/octet-stream';

    private int $filesize = 0;

    private int $httpCode = 200;

    /** @var resource|null */
    private mixed $streamResource = null;

    // ------------------------------------------------------------------
    // ResponseEntityInterface
    // ------------------------------------------------------------------

    /** @return array<mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<mixed> $data */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function setHttpCode(int $code): void
    {
        $this->httpCode = $code;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function isStream(): bool
    {
        return $this->stream;
    }

    // ------------------------------------------------------------------
    // JSON mode helpers
    // ------------------------------------------------------------------

    public function setJson(string $json): void
    {
        /** @var array<mixed> $decoded */
        $decoded    = json_decode($json, associative: true) ?? [];
        $this->data = $decoded;
    }

    // ------------------------------------------------------------------
    // Stream mode helpers
    // ------------------------------------------------------------------

    /** @param resource $resource */
    public function setStream(mixed $resource): void
    {
        $this->streamResource = $resource;
        $this->stream         = true;
    }

    /** @return resource|null */
    public function getStream(): mixed
    {
        return $this->streamResource;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setFilesize(int $filesize): void
    {
        $this->filesize = $filesize;
    }

    public function getFilesize(): int
    {
        return $this->filesize;
    }

    // ------------------------------------------------------------------
    // JsonSerializable
    // ------------------------------------------------------------------

    /** @return array<mixed> */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
