<?php

declare(strict_types=1);

namespace Directive\Http\Upload\Validators;

use Directive\Http\Upload\FileInfo;
use Directive\Http\Upload\ValidatorException;
use Directive\Http\Upload\ValidatorInterface;

/** Rejects files whose detected MIME type is not in the allowed list. */
final class Mimetype implements ValidatorInterface
{
    /** @var string[] */
    private readonly array $mimetypes;

    /**
     * @param string[] $mimetypes  e.g. ['image/jpeg','image/png','application/pdf']
     */
    public function __construct(array $mimetypes)
    {
        $this->mimetypes = $mimetypes;
    }

    public function validate(FileInfo $fileInfo): void
    {
        if (!in_array($fileInfo->getMimetype(), $this->mimetypes, true)) {
            throw new ValidatorException(
                sprintf(
                    'MIME type "%s" is not allowed. Allowed: %s.',
                    $fileInfo->getMimetype(),
                    implode(', ', $this->mimetypes),
                ),
                $fileInfo,
            );
        }
    }
}
