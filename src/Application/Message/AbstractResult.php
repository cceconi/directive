<?php

declare(strict_types=1);

namespace Directive\Application\Message;

use Directive\Application\Role\AbstractRole;

abstract class AbstractResult implements ResultInterface
{
    private ?PayloadInterface $payload = null;
    private ?FilterInterface $filter = null;
    private ?PresenterInterface $presenter = null;

    public function getData(): mixed
    {
        return $this->presentData();
    }

    public function setPayload(PayloadInterface $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function setFilter(FilterInterface $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function setPresenter(PresenterInterface $presenter): static
    {
        $this->presenter = $presenter;

        return $this;
    }

    public function presentData(?AbstractRole $role = null): mixed
    {
        $data = $this->payload;

        if ($this->filter !== null && $role !== null) {
            $data = $this->filter->filter($data, $role);
        }

        if ($this->presenter !== null) {
            $data = $this->presenter->present($data);
        }

        return $data;
    }
}
