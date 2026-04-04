<?php

declare(strict_types=1);

namespace Directive\Http\Endpoint;

use Directive\Application\Message\ResultInterface;
use Directive\Application\User\DomainUser;
use Directive\Service\Security\WebUserInterface;

/**
 * Idiomatic bridge between an HTTP endpoint and an Application UseCase.
 *
 * Seals compute() and exposes execute() as the single extension point.
 * Subclasses inject their specific UseCaseInterface and call it in execute().
 *
 * Override onSuccess() to change the HTTP response code (e.g. 201 for Create).
 */
abstract class AbstractUseCaseApi extends AbstractApi
{
    /**
     * Implement business logic: call your UseCase and return its result.
     */
    abstract protected function execute(): ResultInterface;

    /**
     * Called with the UseCase result after a successful execute().
     * Override to change response code or enrich the response.
     */
    protected function onSuccess(ResultInterface $result): void
    {
        $this->responseEntity->setData((array) $result->getData());
    }

    final protected function compute(): void
    {
        $this->onSuccess($this->execute());
    }

    /**
     * Convert the authenticated WebUser to a DomainUser for the Application layer.
     */
    final protected function domainUser(): DomainUser
    {
        /** @var WebUserInterface $webUser */
        $webUser = $this->container->get(WebUserInterface::class);
        return DomainUserFactory::fromWebUser($webUser);
    }
}
