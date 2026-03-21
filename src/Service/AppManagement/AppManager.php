<?php

declare(strict_types=1);

namespace Directive\Service\AppManagement;

use Directive\Exception\ConflictException;
use Directive\Exception\DirectiveException;
use Directive\Service\Utils\Json;

final class AppManager
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $filename,
        private readonly Json $json,
    ) {}

    /**
     * Read app information.
     *
     * @return array<string, mixed>
     * @throws DirectiveException|ConflictException
     */
    public function read(string $key): array
    {
        if (!is_file($this->filename)) {
            throw new DirectiveException("App-info file '{$this->filename}' not found.");
        }

        $raw  = file_get_contents($this->filename);
        $data = $this->json->convertStringToArray($raw !== false ? $raw : '{}');
        $info = new AppInfo($data);

        if ($key === 'version') {
            return $info->readVersion();
        }

        if ($key !== $this->secretKey) {
            throw new ConflictException('A business error occurred, please check your inputs.')
                ->withErrors([['type' => 'business', 'property' => 'key', 'message' => 'Bad received key']]);
        }

        return $info->readAll();
    }

    /**
     * Generate serialisable app-info data.
     *
     * @return array<string, mixed>
     */
    public function generateInfo(
        string $name,
        string $version,
        string $internal,
        string $env,
        float $commitId,
        string $branch,
        string $at,
    ): array {
        return [
            'name'     => $name,
            'version'  => $version,
            'internal' => $internal,
            'env'      => $env,
            'commitId' => $commitId,
            'branch'   => $branch,
            'at'       => $at,
        ];
    }

    /** @param array<string, mixed> $info */
    public function write(array $info): void
    {
        file_put_contents($this->filename, $this->json->convertArrayToString($info));
    }
}
