<?php

declare(strict_types=1);

use Directive\Application\Exception\AbstractDomainException;
use Directive\Application\Exception\AccessDeniedException;
use Directive\Application\Exception\BusinessRuleException;
use Directive\Application\Exception\ConflictException;
use Directive\Application\Exception\EntityNotFoundException;
use Directive\Application\Exception\ValidationException;

// ---------------------------------------------------------------------------
// AbstractDomainException
// ---------------------------------------------------------------------------

describe('AbstractDomainException', function () {
    it('is catchable as \\DomainException', function () {
        $caught = null;
        try {
            throw new EntityNotFoundException('not found');
        } catch (\DomainException $e) {
            $caught = $e;
        }
        expect($caught)->toBeInstanceOf(\DomainException::class);
    });

    it('stores and returns context', function () {
        $e = new EntityNotFoundException('User not found', context: ['userId' => 'u-42']);
        expect($e->getContext()['userId'])->toBe('u-42');
    });
});

// ---------------------------------------------------------------------------
// Sub-classes
// ---------------------------------------------------------------------------

describe('EntityNotFoundException', function () {
    it('is an AbstractDomainException', function () {
        $e = new EntityNotFoundException('not found');
        expect($e)->toBeInstanceOf(AbstractDomainException::class);
    });
});

describe('AccessDeniedException', function () {
    it('is an AbstractDomainException', function () {
        $e = new AccessDeniedException('access denied');
        expect($e)->toBeInstanceOf(AbstractDomainException::class);
    });
});

describe('ConflictException', function () {
    it('is an AbstractDomainException', function () {
        $e = new ConflictException('conflict');
        expect($e)->toBeInstanceOf(AbstractDomainException::class);
    });
});

describe('BusinessRuleException', function () {
    it('is an AbstractDomainException', function () {
        $e = new BusinessRuleException('rule violated');
        expect($e)->toBeInstanceOf(AbstractDomainException::class);
    });
});

// ---------------------------------------------------------------------------
// ValidationException
// ---------------------------------------------------------------------------

describe('ValidationException', function () {
    it('is an AbstractDomainException', function () {
        $e = new ValidationException('invalid');
        expect($e)->toBeInstanceOf(AbstractDomainException::class);
    });

    it('stores and returns field errors', function () {
        $e = new ValidationException(
            'Validation failed',
            errors: ['email' => 'Invalid format', 'name' => 'Required'],
        );
        expect($e->getErrors())->toMatchArray([
            'email' => 'Invalid format',
            'name'  => 'Required',
        ]);
    });

    it('getErrors() returns empty array when no errors provided', function () {
        $e = new ValidationException('invalid');
        expect($e->getErrors())->toBe([]);
    });
});
