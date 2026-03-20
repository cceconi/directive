<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Adapters\Clamav\Exception;

class UnexpectedResultsException extends ClamavException
{
    /** @param string[] $results */
    public function __construct(array $results)
    {
        parent::__construct(implode(PHP_EOL, $results), 500);
    }
}
