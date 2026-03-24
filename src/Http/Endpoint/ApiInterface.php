<?php

declare(strict_types=1);

namespace Directive\Http\Endpoint;

use Directive\Http\Response\ResponseEntity;

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
