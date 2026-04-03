<?php

declare(strict_types=1);

namespace Tests\Bridge;

use Directive\Application\Message\ResultInterface;
use Directive\Http\Endpoint\AbstractUseCaseApi;
use Directive\Service\Business\ErrorInterface;
use Directive\Http\Request\RequestEntity;
use Directive\Http\Response\ResponseEntity;
use Psr\Container\ContainerInterface;

final class CreateUserApi extends AbstractUseCaseApi
{
    public function __construct(
        ContainerInterface $container,
        ResponseEntity $responseEntity,
        RequestEntity $requestEntity,
        ErrorInterface $businessError,
        private readonly CreateUserUseCaseInterface $useCase,
    ) {
        parent::__construct($container, $responseEntity, $requestEntity, $businessError);
    }

    protected function execute(): ResultInterface
    {
        return $this->useCase->handle(
            new CreateUserCommand(
                email: (string) $this->requestEntity->get('email'),
                caller: $this->domainUser(),
            ),
        );
    }

    protected function onSuccess(ResultInterface $result): void
    {
        parent::onSuccess($result);
        $this->responseEntity->setHttpCode(201);
    }
}
