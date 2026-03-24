<?php

declare(strict_types=1);

use Directive\Rest\NullRequestValidator;
use Directive\Web\RequestEntity;
use Nyholm\Psr7\ServerRequest;

describe('NullRequestValidator', function () {
    it('hasErrors always returns false', function () {
        $v = new NullRequestValidator();
        $v->setRequest(new ServerRequest('GET', '/test'));
        expect($v->hasErrors())->toBeFalse();
    });

    it('hasErrors returns false even without setRequest being called', function () {
        expect((new NullRequestValidator())->hasErrors())->toBeFalse();
    });

    it('getErrors always returns empty array', function () {
        expect((new NullRequestValidator())->getErrors())->toBe([]);
    });

    it('getRequestEntity returns a RequestEntity instance', function () {
        $v = new NullRequestValidator();
        $v->setRequest(new ServerRequest('POST', '/test'));
        expect($v->getRequestEntity())->toBeInstanceOf(RequestEntity::class);
    });

    it('accepts any PSR-7 request without throwing', function () {
        $v = new NullRequestValidator();
        $v->setRequest(new ServerRequest('DELETE', '/foo/bar'));
        $v->getRequestEntity();
        expect($v->hasErrors())->toBeFalse();
    });
});
