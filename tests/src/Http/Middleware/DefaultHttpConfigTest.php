<?php

declare(strict_types=1);

use Directive\Http\Middleware\DefaultHttpConfig;
use Directive\Http\Middleware\HttpConfigInterface;

describe('DefaultHttpConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_RESPONSE_COMPRESS'],
            $_ENV['DIRECTIVE_UPLOAD_TMPDIR'],
            $_ENV['DIRECTIVE_CLIENT_HEADERS'],
        );
    });

    it('implements HttpConfigInterface', function (): void {
        $config = new DefaultHttpConfig();
        $config->audit();
        expect($config)->toBeInstanceOf(HttpConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $config = new DefaultHttpConfig();
        $config->audit();

        expect($config->isCompressionEnabled())->toBeFalse();
        expect($config->getUploadTmpDir())->toBe(sys_get_temp_dir());
        expect($config->getClientHeaderList())->toBe([]);
    });

    it('reads compression flag from env', function (): void {
        $_ENV['DIRECTIVE_RESPONSE_COMPRESS'] = 'true';

        $config = new DefaultHttpConfig();
        $config->audit();

        expect($config->isCompressionEnabled())->toBeTrue();
    });

    it('reads upload tmpdir from env', function (): void {
        $_ENV['DIRECTIVE_UPLOAD_TMPDIR'] = '/tmp/uploads';

        $config = new DefaultHttpConfig();
        $config->audit();

        expect($config->getUploadTmpDir())->toBe('/tmp/uploads');
    });

    it('parses comma-separated client headers', function (): void {
        $_ENV['DIRECTIVE_CLIENT_HEADERS'] = 'X-Client-Id, X-App-Version, X-Request-Source';

        $config = new DefaultHttpConfig();
        $config->audit();

        expect($config->getClientHeaderList())->toBe(['X-Client-Id', 'X-App-Version', 'X-Request-Source']);
    });
});
