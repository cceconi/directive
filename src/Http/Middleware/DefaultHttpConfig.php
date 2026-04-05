<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultHttpConfig implements HttpConfigInterface, ConfigProviderInterface
{
    public function __construct(private Configuration $config) {}

    public function define(Configuration $config): void
    {
        $config->optional('RESPONSE_COMPRESS', false, 'bool');
        $config->optional('UPLOAD_TMPDIR', sys_get_temp_dir(), 'string');
        $config->optional('CLIENT_HEADERS', '', 'string');
    }

    public function isCompressionEnabled(): bool
    {
        return (bool) $this->config->get('RESPONSE_COMPRESS');
    }

    public function getUploadTmpDir(): string
    {
        return (string) $this->config->get('UPLOAD_TMPDIR');
    }

    /** @return array<string> */
    public function getClientHeaderList(): array
    {
        $raw = (string) $this->config->get('CLIENT_HEADERS');

        return $raw !== '' ? array_map('trim', explode(',', $raw)) : [];
    }
}
