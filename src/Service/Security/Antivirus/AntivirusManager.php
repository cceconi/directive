<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

use Directive\Exception\DirectiveException;
use Directive\Service\Security\Antivirus\Adapters\Clamav\ClamavService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class AntivirusManager
{
    /** @var array<string, \Closure(ContainerInterface, AntivirusConfigInterface): AntivirusServiceInterface> */
    private array $adapters = [];

    /** @var array<string, AntivirusServiceInterface> */
    private array $services = [];

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->addAdapter(
            'clamav',
            static function (ContainerInterface $c, AntivirusConfigInterface $config): AntivirusServiceInterface {
                /** @var LoggerInterface $logger */
                $logger = $c->get(LoggerInterface::class);

                return new ClamavService($logger, $config->getHost(), $config->getPort(), $config->getTimeout());
            },
        );
    }

    /** @param \Closure(ContainerInterface, AntivirusConfigInterface): AntivirusServiceInterface $generator */
    public function addAdapter(string $name, \Closure $generator): void
    {
        if (array_key_exists($name, $this->adapters)) {
            throw new DirectiveException("Antivirus adapter '{$name}' already registered");
        }
        $this->adapters[$name] = $generator;
    }

    /** @return \Closure(ContainerInterface, AntivirusConfigInterface): AntivirusServiceInterface */
    private function getAdapterGenerator(string $name): \Closure
    {
        if (!array_key_exists($name, $this->adapters)) {
            throw new DirectiveException("Antivirus adapter '{$name}' not found");
        }
        return $this->adapters[$name];
    }

    public function getAntivirusService(): AntivirusServiceInterface
    {
        /** @var AntivirusConfigInterface $config */
        $config = $this->container->get(AntivirusConfigInterface::class);
        $name   = $config->getName();

        if (!array_key_exists($name, $this->services)) {
            $generator             = $this->getAdapterGenerator($name);
            $this->services[$name] = $generator($this->container, $config);
        }

        return $this->services[$name];
    }
}
