<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Web\ResponseEntity;

/**
 * Contract for all Api handler classes.
 *
 * Epic 4: AbstractApi implements this with preControl() + compute() lifecycle.
 */
interface ApiInterface
{
    public function run(): void;

    public function getResponseEntity(): ResponseEntity;
}
