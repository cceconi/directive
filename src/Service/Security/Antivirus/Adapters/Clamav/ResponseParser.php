<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus\Adapters\Clamav;

use Directive\Service\Security\Antivirus\Analysis\Analysis;
use Directive\Service\Security\Antivirus\Analysis\AnalysisResult;
use Directive\Service\Security\Antivirus\Adapters\Clamav\Exception\UnexpectedResultException;
use Directive\Service\Security\Antivirus\Adapters\Clamav\Exception\UnexpectedResultsException;

final class ResponseParser
{
    /**
     * Parse a single ClamAV response line.
     *
     * Session format:   `1: /path/file: Eicar-Test-Signature FOUND`
     * Single format:    `/path/file: Eicar-Test-Signature FOUND`
     *
     * @throws UnexpectedResultException
     */
    public function parseLine(string $command, string $line): AnalysisResult
    {
        $parts = [];
        preg_match('/^\d?:? ?(\S*): (.*)? ?(OK|FOUND|ERROR)$/', $line, $parts);

        if (4 !== count($parts)) {
            throw new UnexpectedResultException($command, $line);
        }

        $filename = $parts[1];
        $message  = $parts[2] !== '' ? $parts[2] : null;
        $status   = $parts[3];

        return new AnalysisResult($filename, $status, $message);
    }

    /**
     * Parse a multi-line ClamAV response.
     *
     * @throws UnexpectedResultException|UnexpectedResultsException
     */
    public function parse(string $command, string $content): Analysis
    {
        $results = [];
        $errors  = [];

        foreach (explode("\n", $content) as $line) {
            try {
                $results[] = $this->parseLine($command, $line);
            } catch (UnexpectedResultException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new UnexpectedResultsException($errors);
        }

        return new Analysis($results);
    }
}
