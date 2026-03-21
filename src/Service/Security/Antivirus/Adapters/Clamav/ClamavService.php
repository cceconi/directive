<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Adapters\Clamav;

use Directive\Service\Security\Antivirus\Adapters\Clamav\Exception\SocketException;
use Directive\Service\Security\Antivirus\Adapters\Clamav\Sockets\PhpSocket;
use Directive\Service\Security\Antivirus\AntivirusInterface;
use Directive\Service\Security\Antivirus\AntivirusServiceInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Socket\Raw\Exception as SocketRawException;
use Socket\Raw\Factory;

final class ClamavService implements AntivirusServiceInterface
{
    public const int DEFAULT_TIMEOUT = 5;

    private readonly LoggerInterface $logger;

    public function __construct(
        ?LoggerInterface $logger = null,
        private readonly string $host = 'localhost',
        private readonly int $port = 3310,
        private readonly int $timeout = self::DEFAULT_TIMEOUT,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /** @throws SocketException */
    public function create(): AntivirusInterface
    {
        $dsn = sprintf('tcp://%s:%d', $this->host, $this->port);
        try {
            $factory   = new Factory();
            $rawSocket = $factory->createClient($dsn, $this->timeout);
        } catch (SocketRawException $e) {
            $this->logger->error('ClamAV connection error', [
                'message'   => $e->getMessage(),
                'exception' => $e::class,
            ]);
            throw new SocketException('create', 'tcp', ['host' => $this->host, 'port' => $this->port], $e->getCode());
        }

        return new Clamav(new PhpSocket($rawSocket, $this->timeout), $this->logger);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
