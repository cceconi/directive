<?php

declare(strict_types=1);

namespace Directive\Web\InterfaceData;

use Directive\Exception\ConfigurationException;
use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Web\Constraints\FileConstraint;
use Directive\Web\UploadedFile\FileInfo;
use Psr\Http\Message\UploadedFileInterface;

/**
 * InterfaceData for file uploads.
 *
 * Moves the PSR-7 UploadedFile to a configured temp directory and
 * returns a FileInfo value object. Validation is delegated to the
 * injected FileConstraint (which runs all ValidatorInterface instances).
 */
final class File extends InterfaceData
{
    public function __construct(
        private readonly ConfigurationInterface $config,
        FileConstraint $constraint,
        string $errorLabel = '',
    ) {
        parent::__construct($constraint, $errorLabel);
    }

    /**
     * Moves the uploaded file to the configured temp directory and returns
     * a FileInfo instance, or null when the upload failed / no file provided.
     */
    protected function clean(mixed $raw): ?FileInfo
    {
        if (!$raw instanceof UploadedFileInterface) {
            return null;
        }

        if ($raw->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        $clientFilename = $raw->getClientFilename() ?? '';
        $extension      = pathinfo($clientFilename, PATHINFO_EXTENSION);
        $basename       = str_replace('.', '_', uniqid('', true));
        $filename       = sprintf('%s.%0.8s', $basename, $extension);

        $tmpDir = $this->config->get('env.files.tmpdir');

        if (!is_string($tmpDir) || $tmpDir === '') {
            throw new ConfigurationException('Configuration key "env.files.tmpdir" is not set.');
        }

        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0o775, true) && !is_dir($tmpDir)) {
            throw new ConfigurationException(
                sprintf('Temp directory "%s" could not be created.', $tmpDir),
            );
        }

        $filepath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

        $raw->moveTo($filepath);

        return new FileInfo($filepath);
    }
}
