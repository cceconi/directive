<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Adapters\Clamav;

use Directive\Service\Security\Antivirus\Adapters\Clamav\Exception\UnexpectedResultException;
use Directive\Service\Security\Antivirus\Adapters\Clamav\Sockets\PhpSocket;
use Directive\Service\Security\Antivirus\Analysis\Analysis;
use Directive\Service\Security\Antivirus\Analysis\AnalysisResult;
use Directive\Service\Security\Antivirus\AntivirusInterface;
use Psr\Log\LoggerInterface;

class Clamav implements AntivirusInterface
{
    private bool $inSession = false;
    private readonly ResponseParser $responseParser;

    public function __construct(
        private readonly PhpSocket $socket,
        private readonly LoggerInterface $logger,
    ) {
        $this->responseParser = new ResponseParser();
    }

    public function ping(): void
    {
        $ping = $this->sendCommand('PING', true, PhpSocket::BINARY);
        if ('PONG' !== $ping) {
            throw new UnexpectedResultException('PING (ClamAV ping)', $ping);
        }
    }

    public function version(): string
    {
        $version = $this->sendCommand('VERSION', true, PhpSocket::NORMAL);
        if ('' === $version) {
            throw new UnexpectedResultException('VERSION (ClamAV version)', $version);
        }
        return $version;
    }

    public function reload(): void
    {
        $reload = $this->sendCommand('RELOAD', false, PhpSocket::BINARY);
        if ('RELOADING' !== $reload) {
            throw new UnexpectedResultException('RELOAD (ClamAV database reload)', $reload);
        }
    }

    public function shutdown(): void
    {
        $this->sendCommand('SHUTDOWN', false, PhpSocket::BINARY);
    }

    /** @param string[] $paths */
    public function scan(array $paths): Analysis
    {
        $count = count($paths);
        if ($count > 1) {
            $this->startSession();
        }

        $analysis = new Analysis();
        foreach ($paths as $path) {
            $analysis->addAnalysisResult($this->scanPath($path));
        }

        if ($count > 1) {
            $this->endSession();
        }

        return $analysis;
    }

    public function contScan(string $path): Analysis
    {
        $result = $this->sendCommand("CONTSCAN {$path}", false, PhpSocket::BINARY);
        return $this->responseParser->parse("CONTSCAN {$path}", $result);
    }

    public function multiscan(string $path): Analysis
    {
        $result = $this->sendCommand("MULTISCAN {$path}", false, PhpSocket::BINARY);
        return $this->responseParser->parse("MULTISCAN {$path}", $result);
    }

    public function allMatchScan(string $path): Analysis
    {
        $result = $this->sendCommand("ALLMATCHSCAN {$path}", false, PhpSocket::BINARY);
        return $this->responseParser->parse("ALLMATCHSCAN {$path}", $result);
    }

    public function stats(): string
    {
        return $this->sendCommand('STATS', true, PhpSocket::NORMAL, "END\n");
    }

    public function startSession(): void
    {
        $this->inSession = true;
        $this->writeCommand('IDSESSION');
    }

    public function endSession(): void
    {
        $this->writeCommand('END');
        $this->inSession = false;
        $this->socket->disconnect();
    }

    private function scanPath(string $path): AnalysisResult
    {
        if (!file_exists($path) && !is_dir($path)) {
            $this->logger->debug('ClamAV: path not found', ['path' => $path]);
        }
        $result = $this->sendCommand("SCAN {$path}", false, PhpSocket::BINARY);
        return $this->responseParser->parseLine("SCAN {$path}", $result);
    }

    private function writeCommand(string $command): void
    {
        $this->logger->debug('Write command to ClamAV daemon', ['command' => $command]);
        $this->socket->write("n{$command}\n");
    }

    private function sendCommand(
        string $command,
        bool $removeId,
        string $mode,
        string $readUntil = "\n",
    ): string {
        $this->logger->debug('Send command to ClamAV daemon', ['command' => $command]);
        $result = $this->socket->send("n{$command}\n", $removeId, $this->inSession, $mode, $readUntil);
        $this->logger->debug('Receive response from ClamAV daemon', ['response' => $result]);
        return $result;
    }
}
