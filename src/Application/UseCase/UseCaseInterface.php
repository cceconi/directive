<?php

declare(strict_types=1);

namespace Directive\Application\UseCase;

use Directive\Application\Command\AbstractCommand;
use Directive\Application\Message\ResultInterface;
use Directive\Application\Query\AbstractQuery;

interface UseCaseInterface
{
    public function handle(AbstractCommand|AbstractQuery $input): ResultInterface;
}
