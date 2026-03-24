<?php

declare(strict_types=1);

namespace Directive\Http\Upload;

use Directive\Exception\DirectiveException;

/** Thrown by a ValidatorInterface when the file does not pass validation. */
class ValidatorException extends DirectiveException
{
    public function __construct(
        string $message,
        private readonly ?FileInfo $fileInfo = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getFileInfo(): ?FileInfo
    {
        return $this->fileInfo;
    }
}
