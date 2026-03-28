<?php

declare(strict_types=1);

namespace Directive\Service\AppManagement;

use Directive\Http\Middleware\HttpConfigInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ClientHeaders implements ClientHeadersInterface
{
    /** @var array<string, string> */
    private array $data = [];

    public function __construct(private readonly HttpConfigInterface $config) {}

    public function load(ServerRequestInterface $request): void
    {
        $this->data = [];

        /** @var string[] $headerList */
        $headerList = $this->config->getClientHeaderList();

        foreach ($headerList as $name) {
            $this->data[$name] = $request->getHeaderLine($name);
        }
    }

    public function get(string $headerName): ?string
    {
        return $this->data[$headerName] ?? null;
    }
}
