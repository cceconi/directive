<?php

declare(strict_types=1);

namespace Directive\Web\UploadedFile\Validators;

use Directive\Web\UploadedFile\FileInfo;
use Directive\Web\UploadedFile\ValidatorException;
use Directive\Web\UploadedFile\ValidatorInterface;

/** Rejects files whose extension is not in the allowed list. */
final class Extension implements ValidatorInterface
{
    /** @var string[] */
    private readonly array $allowedExtensions;

    /**
     * @param string[] $allowedExtensions  e.g. ['jpg','png','pdf']
     */
    public function __construct(array $allowedExtensions)
    {
        $this->allowedExtensions = array_map('strtolower', $allowedExtensions);
    }

    public function validate(FileInfo $fileInfo): void
    {
        if (!in_array(strtolower($fileInfo->getExtension()), $this->allowedExtensions, true)) {
            throw new ValidatorException(
                sprintf(
                    'Extension "%s" is not allowed. Allowed: %s.',
                    $fileInfo->getExtension(),
                    implode(', ', $this->allowedExtensions),
                ),
                $fileInfo,
            );
        }
    }
}
