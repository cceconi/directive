<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

use Directive\Service\Security\Antivirus\Analysis\Analysis;

interface AntivirusInterface
{
    public function ping(): void;

    public function version(): string;

    public function reload(): void;

    public function shutdown(): void;

    /** @param string[] $paths */
    public function scan(array $paths): Analysis;

    public function contScan(string $path): Analysis;

    public function multiscan(string $path): Analysis;

    public function allMatchScan(string $path): Analysis;

    public function stats(): string;

    public function startSession(): void;

    public function endSession(): void;
}
