<?php

declare(strict_types=1);

use Directive\Http\Upload\FileInfo;

beforeEach(function () {
    $this->tmpFile = tempnam(sys_get_temp_dir(), 'apisy_test_');
    file_put_contents($this->tmpFile, 'hello apisy');
});

afterEach(function () {
    if (is_file($this->tmpFile)) {
        unlink($this->tmpFile);
    }
});

describe('FileInfo', function () {
    it('reports correct size', function () {
        $fi = new FileInfo($this->tmpFile);
        expect($fi->getSize())->toBe(11);
    });

    it('sanitizes a dangerous path to strip directory separators', function () {
        $fi = new FileInfo($this->tmpFile, '../../../etc/passwd');
        // sanitizeName replaces '/' with '_', then basename() strips any remaining path components
        expect($fi->getName())->not->toContain('/');
        expect($fi->getName())->not->toBeEmpty();
    });

    it('stores extension lowercase', function () {
        $fi = new FileInfo($this->tmpFile);
        $fi->setExtension('JPG');
        expect($fi->getExtension())->toBe('jpg');
    });

    it('computes MD5 hash', function () {
        $fi = new FileInfo($this->tmpFile);
        expect($fi->getMd5())->toBe(md5_file($this->tmpFile));
    });

    it('getNameWithExtension returns name.ext', function () {
        $fi = new FileInfo($this->tmpFile);
        $fi->setName('photo');
        $fi->setExtension('jpg');
        expect($fi->getNameWithExtension())->toBe('photo.jpg');
    });

    it('remove() deletes the file', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'apisy_rm_');
        file_put_contents($tmp, 'delete me');
        $fi = new FileInfo($tmp);
        $fi->remove();
        expect(file_exists($tmp))->toBeFalse();
    });
});
