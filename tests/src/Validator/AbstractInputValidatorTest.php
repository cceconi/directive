<?php

declare(strict_types=1);

use Directive\Validator\AbstractInputValidator;

// ---------------------------------------------------------------------------
// Test double — minimal concrete implementation of AbstractInputValidator.
// ---------------------------------------------------------------------------

function makeValidator(callable $registerFn): AbstractInputValidator
{
    return new class ($registerFn) extends AbstractInputValidator {
        public function __construct(private readonly \Closure $registerFn) {}

        protected function register(): void
        {
            ($this->registerFn)($this);
        }
    };
}

// ---------------------------------------------------------------------------
// AbstractInputValidator — hierarchy
// ---------------------------------------------------------------------------

describe('AbstractInputValidator — hierarchy', function () {
    it('AbstractRequestValidator is a subtype of AbstractInputValidator', function () {
        expect(
            is_a(\Directive\Http\Validator\AbstractRequestValidator::class, AbstractInputValidator::class, true),
        )->toBeTrue();
    });

    it('AbstractCommandInputValidator is a subtype of AbstractInputValidator', function () {
        expect(
            is_a(
                \Directive\Cli\Validator\AbstractCommandInputValidator::class,
                AbstractInputValidator::class,
                true,
            ),
        )->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Error management
// ---------------------------------------------------------------------------

describe('AbstractInputValidator — error management', function () {
    it('hasErrors() returns false before any addError()', function () {
        $v = makeValidator(function (AbstractInputValidator $v): void {});
        expect($v->hasErrors())->toBeFalse();
        expect($v->getErrors())->toBe([]);
    });

    it('addError() produces a structured error recoverable by getErrors()', function () {
        $v = makeValidator(function (): void {});

        // Use reflection to call addError (it's protected)
        $ref = new \ReflectionMethod($v, 'addError');
        $ref->invoke($v, 'email', 'Invalid email', 'invalid');

        expect($v->hasErrors())->toBeTrue();
        expect($v->getErrors())->toBe([['property' => 'email', 'message' => 'Invalid email', 'type' => 'invalid']]);
    });

    it('hasErrors() returns true after addError()', function () {
        $v   = makeValidator(function (): void {});
        $ref = new \ReflectionMethod($v, 'addError');
        $ref->invoke($v, 'name', 'Required', 'missing');

        expect($v->hasErrors())->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// reset()
// ---------------------------------------------------------------------------

describe('AbstractInputValidator — reset()', function () {
    it('reset() clears errors accumulated via addError()', function () {
        $v   = makeValidator(function (): void {});
        $ref = new \ReflectionMethod($v, 'addError');
        $ref->invoke($v, 'foo', 'bar');

        $v->reset();

        expect($v->hasErrors())->toBeFalse();
        expect($v->getErrors())->toBe([]);
    });
});

// ---------------------------------------------------------------------------
// guardDuplicate — duplicate field detection
// ---------------------------------------------------------------------------

describe('AbstractInputValidator — duplicate field guard', function () {
    it('throws InvalidArgumentException when the same field name is registered twice', function () {
        $scalar = new \Directive\Input\SimpleString();

        $v = makeValidator(function (AbstractInputValidator $v) use ($scalar): void {
            $refScalar = new \ReflectionMethod($v, 'scalar');
            $refScalar->invoke($v, 'name', $scalar);
            $refScalar->invoke($v, 'name', $scalar); // duplicate
        });

        // We need to call register() — which is called by getErrors() indirectly?
        // No, register() is called by specialised subclasses. We invoke it via reflection.
        $refRegister = new \ReflectionMethod($v, 'register');

        expect(fn() => $refRegister->invoke($v))
            ->toThrow(\InvalidArgumentException::class);
    });
});

// ---------------------------------------------------------------------------
// file() — CLI context blocks it
// ---------------------------------------------------------------------------

describe('AbstractCommandInputValidator — file() not supported in CLI', function () {
    it('throws LogicException when file() is called inside register()', function () {
        $validator = new class extends \Directive\Cli\Validator\AbstractCommandInputValidator {
            protected function register(): void
            {
                $this->file('upload', new \Directive\Input\SimpleString());
            }
        };

        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $validator->setInput($input);

        expect(fn() => $validator->getCommandInputEntity())
            ->toThrow(\LogicException::class, 'file() is not supported in CLI context');
    });
});
