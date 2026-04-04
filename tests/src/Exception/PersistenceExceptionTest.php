<?php

declare(strict_types=1);

use Directive\Exception\DirectiveException;
use Directive\Exception\PersistenceException;

describe('PersistenceException', function (): void {

    it('is an instance of DirectiveException', function (): void {
        expect(new PersistenceException('storage error'))->toBeInstanceOf(DirectiveException::class);
    });

    it('is an instance of RuntimeException', function (): void {
        expect(new PersistenceException('storage error'))->toBeInstanceOf(\RuntimeException::class);
    });

    it('does not extend any Http exception', function (): void {
        $interfaces = class_parents(PersistenceException::class);

        foreach ($interfaces as $parent) {
            expect($parent)->not->toContain('Http');
        }
    });

    it('preserves the message', function (): void {
        $e = new PersistenceException('connection lost');

        expect($e->getMessage())->toBe('connection lost');
    });

    it('preserves the previous exception', function (): void {
        $previous = new \RuntimeException('original driver error');
        $e        = new PersistenceException('wrapped', 0, $previous);

        expect($e->getPrevious())->toBe($previous);
    });

    it('defaults to HTTP 500 (inherited from DirectiveException)', function (): void {
        expect((new PersistenceException('err'))->httpStatus())->toBe(500);
    });
});
