<?php

declare(strict_types=1);

use Directive\Http\Upload\FileInfo;
use Directive\Http\Upload\ValidatorException;
use Directive\Http\Upload\Validators\Dimensions;
use Directive\Http\Upload\Validators\Extension;
use Directive\Http\Upload\Validators\Mimetype;
use Directive\Http\Upload\Validators\Size;

function makeTmpFile(string $content = 'test'): FileInfo
{
    $tmp = tempnam(sys_get_temp_dir(), 'apisy_v_');
    file_put_contents($tmp, $content);
    $fi = new FileInfo($tmp);
    $fi->setExtension('txt');
    return $fi;
}

afterEach(function () {
    // Clean up any leftover tmp files
    foreach (glob(sys_get_temp_dir() . '/apisy_v_*') ?: [] as $f) {
        @unlink($f);
    }
});

describe('Extension validator', function () {
    it('passes an allowed extension', function () {
        $fi = makeTmpFile();
        expect(fn() => new Extension(['txt', 'pdf'])->validate($fi))->not->toThrow(ValidatorException::class);
    });

    it('rejects a disallowed extension', function () {
        $fi = makeTmpFile();
        expect(fn() => new Extension(['jpg', 'png'])->validate($fi))->toThrow(ValidatorException::class);
    });

    it('is case-insensitive', function () {
        $fi = makeTmpFile();
        $fi->setExtension('TXT');
        expect(fn() => new Extension(['txt'])->validate($fi))->not->toThrow(ValidatorException::class);
    });
});

describe('Size validator', function () {
    it('passes a file within limits', function () {
        $fi = makeTmpFile('hello'); // 5 bytes
        expect(fn() => new Size('1mb', '1b')->validate($fi))->not->toThrow(ValidatorException::class);
    });

    it('rejects a file that exceeds max size', function () {
        $fi = makeTmpFile('hello world, this is longer'); // > 10 bytes
        expect(fn() => new Size('10b')->validate($fi))->toThrow(ValidatorException::class);
    });

    it('rejects a file below min size', function () {
        $fi = makeTmpFile('hi'); // 2 bytes
        expect(fn() => new Size('1mb', '10b')->validate($fi))->toThrow(ValidatorException::class);
    });
});

describe('Mimetype validator', function () {
    it('passes a matching MIME type', function () {
        $fi = makeTmpFile('plain text content');
        $mime = $fi->getMimetype(); // detect actual mime
        expect(fn() => new Mimetype([$mime])->validate($fi))->not->toThrow(ValidatorException::class);
    });

    it('rejects a non-matching MIME type', function () {
        $fi = makeTmpFile('plain text content');
        expect(fn() => new Mimetype(['image/jpeg'])->validate($fi))->toThrow(ValidatorException::class);
    });
});

describe('Dimensions validator', function () {
    it('rejects a non-image file (dimensions 0x0)', function () {
        $fi = makeTmpFile('not an image');
        expect(fn() => new Dimensions(100, 100)->validate($fi))->toThrow(ValidatorException::class);
    });
});
