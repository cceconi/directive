<?php

declare(strict_types=1);

use Directive\Application\Message\AbstractResult;
use Directive\Application\Message\FilterInterface;
use Directive\Application\Message\PayloadInterface;
use Directive\Application\Message\PresenterInterface;
use Directive\Application\Message\ResultInterface;
use Directive\Application\Role\AbstractRole;
use Directive\Application\Role\GuestRole;

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

class StringPayload implements PayloadInterface, \Stringable
{
    public function __construct(public readonly mixed $value) {}

    public function __toString(): string
    {
        return (string) $this->value;
    }
}

class ConcreteResult extends AbstractResult
{
    public function __construct(mixed $data)
    {
        $this->setPayload(new StringPayload($data));
    }
}

class UpperCasePresenter implements PresenterInterface
{
    public function present(mixed $data): mixed
    {
        return strtoupper((string) $data);
    }
}

class HideFilterInterface implements FilterInterface
{
    public function filter(mixed $data, AbstractRole $role): mixed
    {
        // Hide everything for guests
        if ($role instanceof GuestRole) {
            return null;
        }

        return $data;
    }
}

// ---------------------------------------------------------------------------
// AbstractResult
// ---------------------------------------------------------------------------

describe('AbstractResult', function () {
    it('implements ResultInterface', function () {
        $result = new ConcreteResult('data');
        expect($result)->toBeInstanceOf(ResultInterface::class);
    });

    it('getData() returns the raw PayloadInterface when no presenter set', function () {
        $result = new ConcreteResult('hello');
        expect($result->getData())->toBeInstanceOf(PayloadInterface::class);
        expect($result->getData())->toBeInstanceOf(StringPayload::class);
    });

    it('getData() applies presenter when set', function () {
        $result = new ConcreteResult('hello');
        $result->setPresenter(new UpperCasePresenter());
        expect($result->getData())->toBe('HELLO');
    });

    it('presentData() applies filter for GuestRole', function () {
        $result = new ConcreteResult('secret');
        $result->setFilter(new HideFilterInterface());
        expect($result->presentData(new GuestRole()))->toBeNull();
    });

    it('presentData() returns raw payload when no role provided (filter skipped)', function () {
        $result = new ConcreteResult('visible');
        $result->setFilter(new HideFilterInterface());
        expect($result->presentData())->toBeInstanceOf(StringPayload::class);
    });

    it('setPayload() returns static for fluent chaining', function () {
        $result = new ConcreteResult('any');
        expect($result->setPayload(new StringPayload('new')))->toBeInstanceOf(ConcreteResult::class);
    });

    it('setPresenter() returns static for fluent chaining', function () {
        $result = new ConcreteResult('any');
        expect($result->setPresenter(new UpperCasePresenter()))->toBeInstanceOf(ConcreteResult::class);
    });
});
