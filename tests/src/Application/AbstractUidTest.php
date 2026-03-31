<?php

declare(strict_types=1);

use Directive\Application\Model\AbstractUid;

// ---------------------------------------------------------------------------
// Concrete stub for testing
// ---------------------------------------------------------------------------

class TestUid extends AbstractUid {}

// ---------------------------------------------------------------------------
// AbstractUid
// ---------------------------------------------------------------------------

describe('AbstractUid', function () {
    it('generates a non-empty UUID v7 when constructed without argument', function () {
        $uid = new TestUid();
        expect($uid->getValue())->not->toBeEmpty();
        // UUID v7 starts with digits — basic format check
        expect(strlen($uid->getValue()))->toBe(36);
    });

    it('preserves the provided value when constructed with a string', function () {
        $value = '018e1234-5678-7abc-def0-123456789abc';
        $uid = new TestUid($value);
        expect($uid->getValue())->toBe($value);
    });

    it('two instances generated without argument have distinct values', function () {
        $uid1 = new TestUid();
        $uid2 = new TestUid();
        expect($uid1->getValue())->not->toBe($uid2->getValue());
    });

    it('equals() returns true for same value', function () {
        $uid1 = new TestUid('018e1234-5678-7abc-def0-123456789abc');
        $uid2 = new TestUid('018e1234-5678-7abc-def0-123456789abc');
        expect($uid1->equals($uid2))->toBeTrue();
    });

    it('equals() returns false for different values', function () {
        $uid1 = new TestUid();
        $uid2 = new TestUid();
        expect($uid1->equals($uid2))->toBeFalse();
    });

    it('__toString() returns the UUID value', function () {
        $uid = new TestUid('018e1234-5678-7abc-def0-123456789abc');
        expect((string) $uid)->toBe('018e1234-5678-7abc-def0-123456789abc');
    });
});
