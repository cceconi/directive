<?php

declare(strict_types=1);

namespace Directive\Console\Generator;

enum ClassType: string
{
    case USE_CASE           = 'usecase.tpl';
    case COMMAND            = 'command.tpl';
    case QUERY              = 'query.tpl';
    case USE_CASE_INTERFACE = 'iusecase.tpl';
    case PAYLOAD            = 'payload.tpl';
    case RESULT             = 'result.tpl';
    case ROLE               = 'role.tpl';
    case EVENT              = 'event.tpl';
    case EVENT_HANDLER      = 'event-handler.tpl';
    case REPOSITORY_INTERFACE = 'repository-interface.tpl';
    case EXCEPTION          = 'exception.tpl';
    case UID                = 'uid.tpl';

    public function outputFilename(string $name): string
    {
        return match ($this) {
            self::USE_CASE           => $name . 'UseCase.php',
            self::COMMAND            => $name . 'Command.php',
            self::QUERY              => $name . 'Query.php',
            self::USE_CASE_INTERFACE => $name . 'UseCaseInterface.php',
            self::PAYLOAD            => $name . 'Payload.php',
            self::RESULT             => $name . 'Result.php',
            self::ROLE               => $name . 'Role.php',
            self::EVENT              => $name . 'DomainEvent.php',
            self::EVENT_HANDLER      => $name . 'EventHandler.php',
            self::REPOSITORY_INTERFACE => $name . 'RepositoryInterface.php',
            self::EXCEPTION          => $name . 'Exception.php',
            self::UID                => $name . 'Id.php',
        };
    }
}
