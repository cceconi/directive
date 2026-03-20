<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Rest\AbstractApi;

/** Minimal API handler that returns a fixed payload for router integration tests. */
final class StubApi extends AbstractApi
{
    protected function compute(): void
    {
        $this->responseEntity->setData(['hello' => 'world']);
    }
}
