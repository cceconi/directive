<?php

declare(strict_types=1);

namespace Directive\Service\Maintenance;

use Directive\Exception\ConflictException;
use Directive\Service\Logging\WebLoggerInterface;
use Directive\Service\Utils\Json;

final class MaintenanceManager implements MaintenanceManagerInterface
{
    private ?MaintenanceInfo $info = null;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $filename,
        private readonly Json $json,
        private readonly WebLoggerInterface $logger,
    ) {}

    // ── MaintenanceManagerInterface ──────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->info?->getMode() ?? false;
    }

    public function getMessage(): string
    {
        return $this->info?->getMessage() ?? 'Application is currently under maintenance.';
    }

    public function getPeriod(): string
    {
        return $this->info !== null ? (string) $this->info->getPeriod() : '0';
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Load maintenance state from the JSON file.
     *
     * @return array<string, mixed>
     */
    public function read(): array
    {
        try {
            if (is_file($this->filename)) {
                $raw  = file_get_contents($this->filename);
                $data = $this->json->convertStringToArray($raw !== false ? $raw : '{}');
                $this->info = MaintenanceInfo::fromArray($data);
            }
            return $this->getInfo();
        } catch (\Throwable $e) {
            $this->logger->logError($e);
        }
        return ['mode' => true, 'period' => 2, 'message' => 'App is currently under maintenance.'];
    }

    /**
     * Apply a new maintenance mode from raw data array.
     *
     * @param array<string, mixed> $data
     * @throws ConflictException when the secret key does not match.
     */
    public function applyMode(array $data, string $providedKey): void
    {
        if ($providedKey !== $this->secretKey) {
            throw new ConflictException('A business error occurred, please check your inputs.')
                ->withErrors([['type' => 'business', 'property' => 'key', 'message' => 'Bad received key']]);
        }

        $this->info = MaintenanceInfo::fromArray($data);
        $this->write($this->info->jsonSerialize());
    }

    /** @return array<string, mixed> */
    public function getInfo(): array
    {
        return $this->info?->jsonSerialize() ?? ['mode' => false];
    }

    // ── Private ──────────────────────────────────────────────────────────────

    /** @param array<string, mixed> $info */
    private function write(array $info): void
    {
        file_put_contents($this->filename, $this->json->convertArrayToString($info));
    }
}
