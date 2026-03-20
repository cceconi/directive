<?php

declare(strict_types=1);

use Directive\Rest\Policy;
use Directive\Web\InterfaceData\SimpleString;
use Directive\Web\Constraints\FormatConstraint;
use Nyholm\Psr7\ServerRequest;

function makeRequest(array $body = []): ServerRequest
{
    $req = new ServerRequest('POST', '/test');
    return $body !== [] ? $req->withParsedBody($body) : $req;
}

function newPolicy(callable $configure): Policy
{
    return new class($configure) extends Policy {
        public function __construct(private readonly \Closure $cfg) {}
        protected function registerScalars(): void { ($this->cfg)($this); }
        protected function registerFiles(): void {}
        protected function registerObjects(): void {}
        protected function registerArrays(): void {}
        // expose addScalar publicly for the test
        public function scalar(string $n, \Directive\Web\InterfaceData\InterfaceDataInterface $f, bool $req = false): void
        {
            $this->addScalar($n, $f, $req);
        }
    };
}

describe('Policy field registration', function () {
    it('collects no errors when optional field is absent', function () {
        $policy = new class extends Policy {
            protected function registerScalars(): void { $this->addEasyScalar('name'); }
            protected function registerFiles(): void {}
            protected function registerObjects(): void {}
            protected function registerArrays(): void {}
        };
        $policy->setRequest(makeRequest([]));
        $policy->getRequestEntity();
        expect($policy->hasErrors())->toBeFalse();
    });

    it('collects error when required field is absent', function () {
        $policy = new class extends Policy {
            protected function registerScalars(): void { $this->addEasyScalar('name', required: true); }
            protected function registerFiles(): void {}
            protected function registerObjects(): void {}
            protected function registerArrays(): void {}
        };
        $policy->setRequest(makeRequest([]));
        $policy->getRequestEntity();

        expect($policy->hasErrors())->toBeTrue();
        expect($policy->getErrors()[0]['property'])->toBe('name');
        expect($policy->getErrors()[0]['type'])->toBe('missing');
    });

    it('collects error when field fails constraint', function () {
        $policy = new class extends Policy {
            protected function registerScalars(): void
            {
                $this->addScalar('email', new \Directive\Web\InterfaceData\EmailAddress());
            }
            protected function registerFiles(): void {}
            protected function registerObjects(): void {}
            protected function registerArrays(): void {}
        };
        $policy->setRequest(makeRequest(['email' => 'not-an-email']));
        $policy->getRequestEntity();

        expect($policy->hasErrors())->toBeTrue();
        expect($policy->getErrors()[0]['property'])->toBe('email');
    });

    it('passes valid data through to RequestEntity', function () {
        $policy = new class extends Policy {
            protected function registerScalars(): void { $this->addEasyScalar('name', required: true); }
            protected function registerFiles(): void {}
            protected function registerObjects(): void {}
            protected function registerArrays(): void {}
        };
        $policy->setRequest(makeRequest(['name' => 'Alice']));
        $entity = $policy->getRequestEntity();

        expect($policy->hasErrors())->toBeFalse();
        expect($entity->get('name'))->toBe('Alice');
    });
});
