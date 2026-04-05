<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultHttpConfig implements HttpConfigInterface
{
    public function __construct(private AbstractConfiguration $config)
    {
        $this->define();
    }

    protected function define(): void
    {
        $this->config->optional('RESPONSE_COMPRESS', false, 'bool');
        $this->config->optional('UPLOAD_TMPDIR', sys_get_temp_dir(), 'string');
        $this->config->optional('CLIENT_HEADERS', '', 'string');
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
