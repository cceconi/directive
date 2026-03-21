<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Analysis;

final class AnalysisResult
{
    public const string OK       = 'OK';
    public const string INFECTED = 'FOUND';
    public const string ERROR    = 'ERROR';

    public function __construct(
        private readonly string $filename,
        private readonly string $status,
        private readonly ?string $message = null,
    ) {}

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isClean(): bool
    {
        return self::OK === $this->status;
    }

    public function isInfected(): bool
    {
        return self::INFECTED === $this->status;
    }

    public function isError(): bool
    {
        return self::ERROR === $this->status;
    }
}
