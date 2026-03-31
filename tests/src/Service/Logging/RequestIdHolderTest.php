<?php

declare(strict_types=1);

use Directive\Service\Logging\RequestId;
use Directive\Service\Logging\RequestIdHolder;

describe('RequestIdHolder', function (): void {

    it('initialises with an empty RequestId', function (): void {
        $holder = new RequestIdHolder();
        expect($holder->get()->value)->toBe('');
    });

    it('stores and retrieves a RequestId', function (): void {
        $holder = new RequestIdHolder();
        $id     = new RequestId('test-uuid');
        $holder->set($id);

        expect($holder->get())->toBe($id);
        expect($holder->get()->value)->toBe('test-uuid');
    });

    it('replaces a previously stored RequestId', function (): void {
        $holder = new RequestIdHolder();
        $holder->set(new RequestId('first'));
        $holder->set(new RequestId('second'));

        expect($holder->get()->value)->toBe('second');
    });
});
