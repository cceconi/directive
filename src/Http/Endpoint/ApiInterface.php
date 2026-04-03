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
    /**
     * Execute the handler. Implementations may throw any DirectiveException or domain exception.
     *
     * @throws \Directive\Http\Exception\TooManyRequestsException
     * @throws \Directive\Http\Exception\BadRequestException
     * @throws \Directive\Http\Exception\ForbiddenException
     * @throws \Directive\Http\Exception\NotFoundException
     * @throws \Directive\Http\Exception\UnauthorizedException
     * @throws \Directive\Http\Exception\UnprocessableException
     * @throws \DomainException May propagate domain exceptions from compute() implementations.
     */
    public function run(): void;

    public function getResponseEntity(): ResponseEntity;
}
