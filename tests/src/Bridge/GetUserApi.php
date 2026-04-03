<?php

declare(strict_types=1);

namespace Tests\Bridge;

use Directive\Application\Message\ResultInterface;
use Directive\Http\Endpoint\AbstractUseCaseApi;
use Directive\Service\Business\ErrorInterface;
use Directive\Http\Request\RequestEntity;
use Directive\Http\Response\ResponseEntity;
use Psr\Container\ContainerInterface;

final class GetUserApi extends AbstractUseCaseApi
{
    public function __construct(
        ContainerInterface $container,
        ResponseEntity $responseEntity,
        RequestEntity $requestEntity,
        ErrorInterface $businessError,
        private readonly GetUserUseCaseInterface $useCase,
    ) {
        parent::__construct($container, $responseEntity, $requestEntity, $businessError);
    }

    protected function execute(): ResultInterface
    {
        return $this->useCase->handle(
            new GetUserQuery(
                id: (string) $this->requestEntity->get('id'),
                caller: $this->domainUser(),
            ),
        );
    }
}
