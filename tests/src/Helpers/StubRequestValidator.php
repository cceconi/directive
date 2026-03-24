<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Http\Validator\AbstractRequestValidator;

/** No-op request validator for router tests. No fields, no validation errors. */
final class StubRequestValidator extends AbstractRequestValidator
{
    protected function register(): void {}
}
