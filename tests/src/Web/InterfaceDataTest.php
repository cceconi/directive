<?php

declare(strict_types=1);

use Directive\Web\InterfaceData\Boolean;
use Directive\Web\InterfaceData\EasyFilenaming;
use Directive\Web\InterfaceData\EmailAddress;
use Directive\Web\InterfaceData\SimpleNumeric;
use Directive\Web\InterfaceData\SimpleString;

describe('SimpleString', function () {
    it('strips HTML tags', function () {
        $f = new SimpleString();
        $f->hydrate('<b>Hello</b>');
        expect($f->getCleanedValue())->toBe('Hello');
    });

    it('converts numeric int to string', function () {
        // SimpleString accepts numeric values (is_numeric(42) = true)
        $f = new SimpleString();
        $f->hydrate(42);
        expect($f->getCleanedValue())->toBe('42');
    });

    it('returns null for array input', function () {
        $f = new SimpleString();
        $f->hydrate(['not', 'a', 'string']);
        expect($f->getCleanedValue())->toBeNull();
    });
});

describe('SimpleNumeric', function () {
    it('casts numeric string to float', function () {
        $f = new SimpleNumeric();
        $f->hydrate('3.14');
        expect($f->getCleanedValue())->toBe(3.14);
    });

    it('returns null for non-numeric string', function () {
        $f = new SimpleNumeric();
        $f->hydrate('abc');
        expect($f->getCleanedValue())->toBeNull();
    });
});

describe('Boolean', function () {
    it('converts 1 and "1" and "true" to true', function () {
        $f = new Boolean();
        foreach ([1, '1', 'true'] as $v) {
            $f->hydrate($v);
            expect($f->getCleanedValue())->toBeTrue("expected to be true for input " . json_encode($v));
        }
    });

    it('converts 0 and "0" and "false" to false', function () {
        $f = new Boolean();
        foreach ([0, '0', 'false'] as $v) {
            $f->hydrate($v);
            expect($f->getCleanedValue())->toBeFalse("expected to be false for input " . json_encode($v));
        }
    });

    it('passes native bool through directly', function () {
        $f = new Boolean();
        $f->hydrate(true);
        expect($f->getCleanedValue())->toBeTrue();
        $f->hydrate(false);
        expect($f->getCleanedValue())->toBeFalse();
    });

    it('returns null for unknown string', function () {
        $f = new Boolean();
        $f->hydrate('yes');
        expect($f->getCleanedValue())->toBeNull();
    });
});

describe('EmailAddress', function () {
    it('passes a valid email', function () {
        $f = new EmailAddress();
        $f->hydrate('user@example.com');
        expect($f->validate())->toBeTrue();
        expect($f->getCleanedValue())->toBe('user@example.com');
    });

    it('fails an invalid email', function () {
        $f = new EmailAddress();
        $f->hydrate('not-an-email');
        expect($f->validate())->toBeFalse();
    });
});

describe('EasyFilenaming', function () {
    it('strips disallowed characters', function () {
        $f = new EasyFilenaming();
        $f->hydrate('my file (1)!.txt');
        expect($f->getCleanedValue())->toBe('myfile1.txt');
    });
});
