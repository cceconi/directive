<?php

declare(strict_types=1);

namespace Directive\Http\Upload\Validators;

use Directive\Http\Upload\FileInfo;
use Directive\Http\Upload\ValidatorException;
use Directive\Http\Upload\ValidatorInterface;

/** Rejects files outside a min/max byte range. Accepts human-readable sizes (e.g. "5mb"). */
final class Size implements ValidatorInterface
{
    private readonly int $maxBytes;
    private readonly int $minBytes;

    public function __construct(string $maxSize, string $minSize = '0b')
    {
        $this->maxBytes = $this->humanReadableToBytes($maxSize);
        $this->minBytes = $this->humanReadableToBytes($minSize);
    }

    public function validate(FileInfo $fileInfo): void
    {
        $size = $fileInfo->getSize();

        if ($size < $this->minBytes || $size > $this->maxBytes) {
            throw new ValidatorException(
                sprintf(
                    'File size %d bytes is outside the allowed range [%d, %d].',
                    $size,
                    $this->minBytes,
                    $this->maxBytes,
                ),
                $fileInfo,
            );
        }
    }

    private function humanReadableToBytes(string $input): int
    {
        $input = strtolower(trim($input));

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(b|kb?|mb?|gb?)$/', $input, $m) !== 1) {
            return 0;
        }

        $num  = (float) $m[1];
        $unit = $m[2];

        return (int) match (true) {
            str_starts_with($unit, 'g') => $num * 1_073_741_824,
            str_starts_with($unit, 'm') => $num * 1_048_576,
            str_starts_with($unit, 'k') => $num * 1_024,
            default                      => $num,
        };
    }
}
