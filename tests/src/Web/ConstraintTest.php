<?php

declare(strict_types=1);

use Directive\Exception\ConstraintException;
use Directive\Web\Constraints\BoundedByConstraint;
use Directive\Web\Constraints\EqualToConstraint;
use Directive\Web\Constraints\FormatConstraint;
use Directive\Web\Constraints\GreaterThanConstraint;
use Directive\Web\Constraints\LesserThanConstraint;
use Directive\Web\Constraints\ListValuesConstraint;
use Directive\Web\Constraints\NoConstraint;
use Directive\Web\Constraints\NotEqualToConstraint;

describe('NoConstraint', function () {
    it('always passes', function () {
        expect(new NoConstraint()->checkConstraint('anything'))->toBeTrue();
        expect(new NoConstraint()->checkConstraint(null))->toBeTrue();
    });
});

describe('FormatConstraint', function () {
    it('passes a matching value', function () {
        $c = new FormatConstraint('/^\d+$/');
        expect($c->checkConstraint('42'))->toBeTrue();
    });

    it('rejects a non-matching value', function () {
        $c = new FormatConstraint('/^\d+$/');
        expect($c->checkConstraint('abc'))->toBeFalse();
    });

    it('throws ConstraintException on invalid regex', function () {
        // Use a properly delimited but syntactically invalid pattern (unclosed character class).
        // This avoids the "Delimiter must not be alphanumeric" PHP warning triggered by bare strings.
        expect(fn() => new FormatConstraint('/[unclosed/'))->toThrow(ConstraintException::class);
    });
});

describe('EqualToConstraint', function () {
    it('passes equal value', function () {
        expect(new EqualToConstraint('hello')->checkConstraint('hello'))->toBeTrue();
    });

    it('rejects different value', function () {
        expect(new EqualToConstraint('hello')->checkConstraint('world'))->toBeFalse();
    });
});

describe('NotEqualToConstraint', function () {
    it('passes different value', function () {
        expect(new NotEqualToConstraint('bad')->checkConstraint('good'))->toBeTrue();
    });

    it('rejects equal value', function () {
        expect(new NotEqualToConstraint('bad')->checkConstraint('bad'))->toBeFalse();
    });
});

describe('GreaterThanConstraint', function () {
    it('passes value greater than threshold', function () {
        expect(new GreaterThanConstraint(5)->checkConstraint(10))->toBeTrue();
    });

    it('rejects value equal to or below threshold', function () {
        expect(new GreaterThanConstraint(5)->checkConstraint(5))->toBeFalse();
        expect(new GreaterThanConstraint(5)->checkConstraint(3))->toBeFalse();
    });
});

describe('LesserThanConstraint', function () {
    it('passes value less than threshold', function () {
        expect(new LesserThanConstraint(10)->checkConstraint(5))->toBeTrue();
    });

    it('rejects value equal to or above threshold', function () {
        expect(new LesserThanConstraint(10)->checkConstraint(10))->toBeFalse();
        expect(new LesserThanConstraint(10)->checkConstraint(15))->toBeFalse();
    });
});

describe('BoundedByConstraint', function () {
    it('passes value in range', function () {
        expect(new BoundedByConstraint(1, 10)->checkConstraint(5))->toBeTrue();
    });

    it('rejects value outside range', function () {
        expect(new BoundedByConstraint(1, 10)->checkConstraint(0))->toBeFalse();
        expect(new BoundedByConstraint(1, 10)->checkConstraint(11))->toBeFalse();
    });
});

describe('ListValuesConstraint', function () {
    it('passes value in allowed list', function () {
        expect(new ListValuesConstraint(['a', 'b', 'c'])->checkConstraint('b'))->toBeTrue();
    });

    it('rejects value not in allowed list', function () {
        expect(new ListValuesConstraint(['a', 'b'])->checkConstraint('z'))->toBeFalse();
    });
});
