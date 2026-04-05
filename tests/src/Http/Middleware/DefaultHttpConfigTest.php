<?php

declare(strict_types=1);

use Directive\Http\Middleware\DefaultHttpConfig;
use Directive\Http\Middleware\HttpConfigInterface;

describe('DefaultHttpConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['RESPONSE_COMPRESS'],
            $_ENV['UPLOAD_TMPDIR'],
            $_ENV['CLIENT_HEADERS'],
        );
    });

    it('implements HttpConfigInterface', function (): void {
        $shared = makeTestConfig();
        $config = new DefaultHttpConfig($shared);
        $config->define($shared);
        $shared->audit();
        expect($config)->toBeInstanceOf(HttpConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $shared = makeTestConfig();
        $config = new DefaultHttpConfig($shared);
        $config->define($shared);
        $shared->audit();

        expect($config->isCompressionEnabled())->toBeFalse();
        expect($config->getUploadTmpDir())->toBe(sys_get_temp_dir());
        expect($config->getClientHeaderList())->toBe([]);
    });

    it('reads compression flag from env', function (): void {
        $_ENV['RESPONSE_COMPRESS'] = 'true';

        $shared = makeTestConfig();
        $config = new DefaultHttpConfig($shared);
        $config->define($shared);
        $shared->audit();

        expect($config->isCompressionEnabled())->toBeTrue();
    });

    it('reads upload tmpdir from env', function (): void {
        $_ENV['UPLOAD_TMPDIR'] = '/tmp/uploads';

        $shared = makeTestConfig();
        $config = new DefaultHttpConfig($shared);
        $config->define($shared);
        $shared->audit();

        expect($config->getUploadTmpDir())->toBe('/tmp/uploads');
    });

    it('parses comma-separated client headers', function (): void {
        $_ENV['CLIENT_HEADERS'] = 'X-Client-Id, X-App-Version, X-Request-Source';

        $shared = makeTestConfig();
        $config = new DefaultHttpConfig($shared);
        $config->define($shared);
        $shared->audit();

        expect($config->getClientHeaderList())->toBe(['X-Client-Id', 'X-App-Version', 'X-Request-Source']);
    });
});
