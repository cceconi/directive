<?php

declare(strict_types=1);

namespace Directive\Http\Upload\Validators;

use Directive\Http\Upload\FileInfo;
use Directive\Http\Upload\ValidatorException;
use Directive\Http\Upload\ValidatorInterface;

/** Rejects images that do not match exact expected dimensions. */
final class Dimensions implements ValidatorInterface
{
    public function __construct(
        private readonly int $expectedWidth,
        private readonly int $expectedHeight,
    ) {}

    public function validate(FileInfo $fileInfo): void
    {
        $dims = $fileInfo->getDimensions();

        if ($dims['width'] !== $this->expectedWidth || $dims['height'] !== $this->expectedHeight) {
            throw new ValidatorException(
                sprintf(
                    'Image dimensions %dx%d do not match expected %dx%d.',
                    $dims['width'],
                    $dims['height'],
                    $this->expectedWidth,
                    $this->expectedHeight,
                ),
                $fileInfo,
            );
        }
    }
}
