<?php

declare(strict_types=1);

use Directive\Input\SimpleString;
use Directive\Http\Validator\AbstractRequestValidator;
use Nyholm\Psr7\ServerRequest;

/** @param array<string, mixed> $body */
function makeRequest(array $body = []): ServerRequest
{
    $req = new ServerRequest('POST', '/test');
    return $body !== [] ? $req->withParsedBody($body) : $req;
}

describe('AbstractRequestValidator field registration', function () {
    it('collects no errors when optional field is absent', function () {
        $validator = new class extends AbstractRequestValidator {
            protected function register(): void
            {
                $this->scalar('name', new SimpleString());
            }
        };
        $validator->setRequest(makeRequest([]));
        $validator->getRequestEntity();
        expect($validator->hasErrors())->toBeFalse();
    });

    it('collects error when required field is absent', function () {
        $validator = new class extends AbstractRequestValidator {
            protected function register(): void
            {
                $this->scalar('name', new SimpleString(), required: true);
            }
        };
        $validator->setRequest(makeRequest([]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('name');
        expect($validator->getErrors()[0]['type'])->toBe('missing');
    });

    it('collects error when field fails constraint', function () {
        $validator = new class extends AbstractRequestValidator {
            protected function register(): void
            {
                $this->scalar('email', new \Directive\Input\EmailAddress());
            }
        };
        $validator->setRequest(makeRequest(['email' => 'not-an-email']));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('email');
    });

    it('passes valid data through to RequestEntity', function () {
        $validator = new class extends AbstractRequestValidator {
            protected function register(): void
            {
                $this->scalar('name', new SimpleString(), required: true);
            }
        };
        $validator->setRequest(makeRequest(['name' => 'Alice']));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('name'))->toBe('Alice');
    });
});
