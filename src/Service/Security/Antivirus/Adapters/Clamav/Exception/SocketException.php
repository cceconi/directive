<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Adapters\Clamav\Exception;

class SocketException extends ClamavException
{
    /** @param array<string,int|string> $params */
    public function __construct(string $mode, string $type, array $params, int $errorCode)
    {
        $strParams = PHP_EOL;
        foreach ($params as $key => $value) {
            $strParams .= "   {$key} => {$value}" . PHP_EOL;
        }
        $strParams .= "   error code => {$errorCode}" . PHP_EOL;
        parent::__construct("Cannot {$mode} socket {$type} with params : {$strParams}", 200);
    }
}
