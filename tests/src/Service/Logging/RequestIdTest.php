<?php

declare(strict_types=1);

use Directive\Service\Logging\RequestId;

describe('RequestId', function (): void {

    it('holds the provided value', function (): void {
        $id = new RequestId('abc-123');
        expect($id->value)->toBe('abc-123');
    });

    it('defaults to an empty string', function (): void {
        $id = new RequestId();
        expect($id->value)->toBe('');
    });

    it('is a readonly class', function (): void {
        $ref = new ReflectionClass(RequestId::class);
        expect($ref->isReadOnly())->toBeTrue();
    });
});
