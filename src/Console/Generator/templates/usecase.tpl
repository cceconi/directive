<?php

declare(strict_types=1);

namespace {{namespace}}\UseCase\{{business}};

use Directive\Application\Command\AbstractCommand;
use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\Message\ResultInterface;
use Directive\Application\Query\AbstractQuery;
use Directive\Application\UseCase\AbstractUseCase;
use {{namespace}}\Api\{{business}}\{{name}}UseCaseInterface;
use {{namespace}}\UseCase\{{business}}\{{name}}{{input}};
use {{namespace}}\UseCase\{{business}}\{{name}}Result;
use Psr\Log\LoggerInterface;

final class {{name}}UseCase extends AbstractUseCase implements {{name}}UseCaseInterface
{
    public function __construct(
        LoggerInterface $logger,
        DomainEventBusInterface $eventBus,
    ) {
        parent::__construct($logger, $eventBus);
    }

    protected function execute(AbstractCommand|AbstractQuery $input): ResultInterface
    {
        /** @var {{name}}{{input}} $input */
        return new {{name}}Result();
    }
}
