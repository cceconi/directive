<?php

declare(strict_types=1);

namespace Directive\Service\Health;

readonly class HealthCheckResponse
{
    /**
     * @param array<string, list<array<string, mixed>>> $checks
     */
    public function __construct(
        public HealthStatus $status,
        public string $version,
        public string $releaseId,
        public array $checks = [],
        public ?string $description = null,
    ) {}

    /**
     * Serialise to an IETF Health Check Response Format array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'status'    => $this->status->value(),
            'version'   => $this->version,
            'releaseId' => $this->releaseId,
            'checks'    => $this->checks,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
