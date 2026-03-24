<?php

declare(strict_types=1);

namespace Directive\Http\Response;

/**
 * Contract for all response entities.
 *
 * Dual-mode:
 *   - JSON  : getData() / setData()
 *   - Stream: setStream() + filename + content-type + filesize
 */
interface ResponseEntityInterface
{
    // ------------------------------------------------------------------
    // JSON mode
    // ------------------------------------------------------------------

    /** @return array<mixed> */
    public function getData(): array;

    /** @param array<mixed> $data */
    public function setData(array $data): void;

    // ------------------------------------------------------------------
    // Stream mode
    // ------------------------------------------------------------------

    public function isStream(): bool;

    /** @param resource $resource */
    public function setStream(mixed $resource): void;

    /** @return resource|null */
    public function getStream(): mixed;

    public function setFilename(string $filename): void;

    public function getFilename(): string;

    public function setContentType(string $contentType): void;

    public function getContentType(): string;

    public function setFilesize(int $filesize): void;

    public function getFilesize(): int;
}
