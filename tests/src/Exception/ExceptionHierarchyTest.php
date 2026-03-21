<?php

declare(strict_types=1);

use Directive\Exception\BadRequestException;
use Directive\Exception\ConflictException;
use Directive\Exception\DirectiveException;
use Directive\Exception\ForbiddenException;
use Directive\Exception\GoneException;
use Directive\Exception\MethodNotAllowedException;
use Directive\Exception\NotFoundException;
use Directive\Exception\UnauthorizedException;
use Directive\Exception\WebUserException;

describe('DirectiveException hierarchy', function () {
    it('DirectiveException defaults to HTTP 500', function () {
        expect(new DirectiveException('test')->httpStatus())->toBe(500);
    });

    it('BadRequestException returns 400', function () {
        expect(new BadRequestException('bad')->httpStatus())->toBe(400);
    });

    it('UnauthorizedException returns 401', function () {
        expect(new UnauthorizedException('unauth')->httpStatus())->toBe(401);
    });

    it('ForbiddenException returns 403', function () {
        expect(new ForbiddenException('forbidden')->httpStatus())->toBe(403);
    });

    it('NotFoundException returns 404', function () {
        expect(new NotFoundException('not found')->httpStatus())->toBe(404);
    });

    it('MethodNotAllowedException returns 405', function () {
        expect(new MethodNotAllowedException('method')->httpStatus())->toBe(405);
    });

    it('ConflictException returns 409', function () {
        expect(new ConflictException('conflict')->httpStatus())->toBe(409);
    });

    it('GoneException returns 410', function () {
        expect(new GoneException('gone')->httpStatus())->toBe(410);
    });

    it('WebUserException returns 403', function () {
        expect(new WebUserException('web user')->httpStatus())->toBe(403);
    });
});

describe('ConflictException::withErrors()', function () {
    it('returns a clone with errors attached', function () {
        $original = new ConflictException('conflict');
        $withErr  = $original->withErrors([['property' => 'x', 'message' => 'msg', 'type' => 'invalid']]);

        expect($original->getErrors())->toBe([]);
        expect($withErr->getErrors())->toHaveCount(1);
        expect($withErr->getErrors()[0]['property'])->toBe('x');
    });

    it('BadRequestException inherits withErrors()', function () {
        $e = new BadRequestException('bad')->withErrors([['property' => 'f', 'message' => 'err', 'type' => 'missing']]);
        expect($e->httpStatus())->toBe(400);
        expect($e->getErrors()[0]['property'])->toBe('f');
    });
});
