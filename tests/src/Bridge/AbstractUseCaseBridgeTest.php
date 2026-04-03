<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Directive\Application\Message\ResultInterface;
use Directive\Application\User\DomainUser;
use Directive\Http\Request\RequestEntity;
use Directive\Http\Response\ResponseEntity;
use Directive\Service\Business\ErrorInterface;
use Directive\Service\Business\ErrorManager;
use Directive\Service\Security\WebUserInterface;
use Tests\Bridge\CreateUserApi;
use Tests\Bridge\CreateUserPayload;
use Tests\Bridge\CreateUserResult;
use Tests\Bridge\CreateUserUseCaseInterface;
use Tests\Bridge\GetUserApi;
use Tests\Bridge\GetUserPayload;
use Tests\Bridge\GetUserResult;
use Tests\Bridge\GetUserUseCaseInterface;
use Tests\Helpers\StubWebUser;

require_once __DIR__ . '/GetUserStubs.php';
require_once __DIR__ . '/CreateUserStubs.php';
require_once __DIR__ . '/GetUserApi.php';
require_once __DIR__ . '/CreateUserApi.php';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeBridgeContainer(?WebUserInterface $webUser = null): \Psr\Container\ContainerInterface
{
    $builder = new ContainerBuilder();
    $builder->addDefinitions([
        ErrorInterface::class   => \DI\factory(fn () => new ErrorManager()),
        WebUserInterface::class => \DI\factory(fn () => $webUser ?? new StubWebUser()),
    ]);
    return $builder->build();
}

/** @return RequestEntity&object */
function makeBridgeRequestEntity(): RequestEntity
{
    return new class extends RequestEntity {};
}

function makeGetUserApi(GetUserUseCaseInterface $useCase, ?WebUserInterface $webUser = null): GetUserApi
{
    return new GetUserApi(
        makeBridgeContainer($webUser),
        new ResponseEntity(),
        makeBridgeRequestEntity(),
        new ErrorManager(),
        $useCase,
    );
}

function makeCreateUserApi(CreateUserUseCaseInterface $useCase, ?WebUserInterface $webUser = null): CreateUserApi
{
    return new CreateUserApi(
        makeBridgeContainer($webUser),
        new ResponseEntity(),
        makeBridgeRequestEntity(),
        new ErrorManager(),
        $useCase,
    );
}

// ---------------------------------------------------------------------------
// AbstractUseCaseBridgeTest
// ---------------------------------------------------------------------------

describe('AbstractUseCaseApi', function () {
    it('compute() calls execute() and writes result data to responseEntity (200)', function () {
        $useCase = new class implements GetUserUseCaseInterface {
            public function handle(\Directive\Application\Command\AbstractCommand|\Directive\Application\Query\AbstractQuery $input): ResultInterface
            {
                return new GetUserResult(new GetUserPayload('u-42', 'Alice'));
            }
        };

        $api = makeGetUserApi($useCase);
        $api->run();

        expect($api->getResponseEntity()->getData())->toBeArray();
        expect($api->getResponseEntity()->getHttpCode())->toBe(200);
    });

    it('onSuccess() override in CreateUserApi emits 201', function () {
        $useCase = new class implements CreateUserUseCaseInterface {
            public function handle(\Directive\Application\Command\AbstractCommand|\Directive\Application\Query\AbstractQuery $input): ResultInterface
            {
                return new CreateUserResult(new CreateUserPayload('u-new', 'alice@example.com'));
            }
        };

        $api = makeCreateUserApi($useCase);
        $api->run();

        expect($api->getResponseEntity()->getHttpCode())->toBe(201);
    });

    it('domainUser() returns a DomainUser built from the WebUser', function () {
        $webUser = new StubWebUser();
        $useCase = new class implements GetUserUseCaseInterface {
            public ?\Directive\Application\User\DomainUser $capturedCaller = null;

            public function handle(\Directive\Application\Command\AbstractCommand|\Directive\Application\Query\AbstractQuery $input): ResultInterface
            {
                /** @var \Tests\Bridge\GetUserQuery $input */
                $this->capturedCaller = $input->caller;
                return new GetUserResult(new GetUserPayload($input->id, 'Test'));
            }
        };

        $api = makeGetUserApi($useCase, $webUser);
        $api->run();

        expect($useCase->capturedCaller)->toBeInstanceOf(DomainUser::class);
        expect($useCase->capturedCaller?->id)->toBe($webUser->getId());
        expect($useCase->capturedCaller?->role)->toBeInstanceOf($webUser->getRole()::class);
    });
});
