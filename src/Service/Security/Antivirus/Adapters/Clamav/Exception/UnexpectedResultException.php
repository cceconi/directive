<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Adapters\Clamav\Exception;

class UnexpectedResultException extends ClamavException
{
    public function __construct(string $command, string $result)
    {
        parent::__construct("Command {$command} Unexpected result {$result}", 400);
    }
}
