<?php

declare(strict_types=1);

use Directive\Http\Routing\MethodDefaults;

describe('MethodDefaults', function () {
    it('starts with all null fields', function () {
        $d = new MethodDefaults();
        expect($d->errorClass)->toBeNull();
        expect($d->requestValidatorClass)->toBeNull();
        expect($d->allowedRoles)->toBeNull();
    });

    it('withErrorClass returns new instance with updated errorClass', function () {
        $original = new MethodDefaults();
        $updated  = $original->withErrorClass('App\\MyErrorManager');

        expect($updated->errorClass)->toBe('App\\MyErrorManager');
        expect($original->errorClass)->toBeNull(); // original unchanged
        expect($updated->requestValidatorClass)->toBeNull();
        expect($updated->allowedRoles)->toBeNull();
    });

    it('withRequestValidatorClass returns new instance with updated requestValidatorClass', function () {
        $original = new MethodDefaults(errorClass: 'App\\ErrorManager');
        $updated  = $original->withRequestValidatorClass('App\\MyValidator');

        expect($updated->requestValidatorClass)->toBe('App\\MyValidator');
        expect($original->requestValidatorClass)->toBeNull(); // original unchanged
        expect($updated->errorClass)->toBe('App\\ErrorManager'); // preserved
    });

    it('withAllowedRoles returns new instance with updated allowedRoles', function () {
        $original = new MethodDefaults();
        $updated  = $original->withAllowedRoles(['admin', 'user']);

        expect($updated->allowedRoles)->toBe(['admin', 'user']);
        expect($original->allowedRoles)->toBeNull(); // original unchanged
    });

    it('empty array for allowedRoles is a concrete value (not null)', function () {
        $d = (new MethodDefaults())->withAllowedRoles([]);
        expect($d->allowedRoles)->toBe([]);
        expect($d->allowedRoles)->not->toBeNull();
    });

    it('chained with* calls accumulate correctly', function () {
        $d = (new MethodDefaults())
            ->withErrorClass('App\\ErrorManager')
            ->withRequestValidatorClass('App\\Validator')
            ->withAllowedRoles(['admin']);

        expect($d->errorClass)->toBe('App\\ErrorManager');
        expect($d->requestValidatorClass)->toBe('App\\Validator');
        expect($d->allowedRoles)->toBe(['admin']);
    });

    it('withAllowedRoles replaces rather than merges', function () {
        $first  = (new MethodDefaults())->withAllowedRoles(['admin']);
        $second = $first->withAllowedRoles(['user']);

        expect($second->allowedRoles)->toBe(['user']);
        expect($second->allowedRoles)->not->toContain('admin');
    });
});
