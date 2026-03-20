<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Rest\Policy;

/** No-op policy for router tests. No fields, no validation errors. */
final class StubPolicy extends Policy
{
    protected function registerScalars(): void {}
    protected function registerFiles(): void {}
    protected function registerObjects(): void {}
    protected function registerArrays(): void {}
}
