<?php

declare(strict_types=1);

namespace Directive\Http\Upload;

/**
 * Contract for all file validators.
 *
 * Throw ValidatorException on failure; return normally on success.
 */
interface ValidatorInterface
{
    /**
     * @throws ValidatorException
     */
    public function validate(FileInfo $fileInfo): void;
}
