<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Adapters\Clamav\Sockets;

use Directive\Service\Security\Antivirus\Adapters\Clamav\Exception\SocketTimeoutException;
use Socket\Raw\Socket as RawSocket;

final class PhpSocket
{
    public const string NORMAL = 'normal';
    public const string BINARY = 'binary';

    private int $timeout;

    public function __construct(
        private readonly RawSocket $socket,
        int $timeout = 30,
    ) {
        $this->timeout = $timeout;
    }

    public function disconnect(): void
    {
        $this->socket->close();
    }

    public function write(string $command): void
    {
        $this->socket->send($command, MSG_DONTROUTE);
    }

    public function send(
        string $command,
        bool $removeId,
        bool $inSession,
        string $mode,
        string $readUntil = "\n",
    ): string {
        $this->socket->send($command, MSG_DONTROUTE);

        $result       = '';
        $readUntilLen = strlen($readUntil);

        do {
            $recv = $this->readSocket($mode === self::NORMAL ? PHP_NORMAL_READ : PHP_BINARY_READ);
            if ($recv === '') {
                break;
            }
            $result .= $recv;
            if ($mode === self::NORMAL && strcmp(substr($result, 0 - $readUntilLen), $readUntil) === 0) {
                break;
            }
        } while (true);

        return $this->postAction($result, $removeId, $inSession);
    }

    private function readSocket(int $socketMode): string
    {
        if (!$this->socket->selectRead($this->timeout)) {
            throw new SocketTimeoutException('Timeout waiting to read response');
        }
        return $this->socket->read(8192, $socketMode);
    }

    private function postAction(string $result, bool $removeId, bool $inSession): string
    {
        if (!$inSession) {
            $this->socket->close();
            return trim($result);
        }

        if ($removeId) {
            return (string) preg_replace('/^\d+: /', '', $result, 1);
        }

        return trim($result);
    }
}
