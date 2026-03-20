<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Analysis;

final class Analysis
{
    /** @var array<string,AnalysisResult> */
    private array $data = [];

    /** @param AnalysisResult[] $results */
    public function __construct(array $results = [])
    {
        foreach ($results as $result) {
            $this->addAnalysisResult($result);
        }
    }

    public function addResult(string $filename, string $status, ?string $message = null): void
    {
        $this->addAnalysisResult(new AnalysisResult($filename, $status, $message));
    }

    public function addAnalysisResult(AnalysisResult $result): void
    {
        $this->data[$result->getFilename()] = $result;
    }

    /** @return array<string,AnalysisResult> */
    public function all(): array
    {
        return $this->data;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function get(string $file): ?AnalysisResult
    {
        return $this->data[$file] ?? null;
    }
}
