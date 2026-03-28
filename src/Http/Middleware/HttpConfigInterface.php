<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

interface HttpConfigInterface
{
    public function isCompressionEnabled(): bool;
    public function getUploadTmpDir(): string;
    /** @return array<string> */
    public function getClientHeaderList(): array;
}
