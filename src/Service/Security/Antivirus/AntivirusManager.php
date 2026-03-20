<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

use Directive\Exception\DirectiveException;
use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Service\Security\Antivirus\Adapters\Clamav\ClamavService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class AntivirusManager
{
    /** @var array<string,\Closure(ContainerInterface,ConfigurationInterface):AntivirusServiceInterface> */
    private array $adapters = [];

    /** @var array<string,AntivirusServiceInterface> */
    private array $services = [];

    public function __construct(private readonly ContainerInterface $container)
    {
        $this->addAdapter(
            'clamav',
            static function (ContainerInterface $c, ConfigurationInterface $config): AntivirusServiceInterface {
                /** @var LoggerInterface $logger */
                $logger  = $c->get(LoggerInterface::class);
                $host    = (string) $config->get('env.antivirus.host');
                $port    = (int)    $config->get('env.antivirus.port');
                $timeout = (int)    $config->get('env.antivirus.timeout');
                return new ClamavService($logger, $host, $port, $timeout);
            }
        );
    }

    /** @param \Closure(ContainerInterface,ConfigurationInterface):AntivirusServiceInterface $generator */
    public function addAdapter(string $name, \Closure $generator): void
    {
        if (array_key_exists($name, $this->adapters)) {
            throw new DirectiveException("Antivirus adapter '{$name}' already registered");
        }
        $this->adapters[$name] = $generator;
    }

    /** @return \Closure(ContainerInterface,ConfigurationInterface):AntivirusServiceInterface */
    private function getAdapterGenerator(string $name): \Closure
    {
        if (!array_key_exists($name, $this->adapters)) {
            throw new DirectiveException("Antivirus adapter '{$name}' not found");
        }
        return $this->adapters[$name];
    }

    public function getAntivirusService(): AntivirusServiceInterface
    {
        /** @var ConfigurationInterface $config */
        $config = $this->container->get(ConfigurationInterface::class);
        $name   = (string) $config->get('env.antivirus.name');

        if (!array_key_exists($name, $this->services)) {
            $generator            = $this->getAdapterGenerator($name);
            $this->services[$name] = $generator($this->container, $config);
        }

        return $this->services[$name];
    }
}
