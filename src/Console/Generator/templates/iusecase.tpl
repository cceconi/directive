<?php

declare(strict_types=1);

namespace {{namespace}}\Api\{{business}};

use Directive\Application\UseCase\UseCaseInterface;

/**
 * DI marker interface for {{name}} use case.
 * Inherit the typed contract via {@see UseCaseInterface::handle()}.
 */
interface {{name}}UseCaseInterface extends UseCaseInterface
{
}
