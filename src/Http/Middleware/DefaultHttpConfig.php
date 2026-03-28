<?php

declare(strict_types=1);

namespace Directive\Http\Middleware;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultHttpConfig extends AbstractConfiguration implements HttpConfigInterface
{
    protected function define(): void
    {
        $this->optional('DIRECTIVE_RESPONSE_COMPRESS', false, 'bool');
        $this->optional('DIRECTIVE_UPLOAD_TMPDIR', sys_get_temp_dir(), 'string');
        $this->optional('DIRECTIVE_CLIENT_HEADERS', '', 'string');
    }

    public function isCompressionEnabled(): bool
    {
        return (bool) $this->get('DIRECTIVE_RESPONSE_COMPRESS');
    }

    public function getUploadTmpDir(): string
    {
        return (string) $this->get('DIRECTIVE_UPLOAD_TMPDIR');
    }

    /** @return array<string> */
    public function getClientHeaderList(): array
    {
        $raw = (string) $this->get('DIRECTIVE_CLIENT_HEADERS');

        return $raw !== '' ? array_map('trim', explode(',', $raw)) : [];
    }
}
