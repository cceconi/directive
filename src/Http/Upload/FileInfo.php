<?php

declare(strict_types=1);

namespace Directive\Http\Upload;

/**
 * Enriched file value object built on top of SplFileInfo.
 *
 * Adds name sanitisation, lazy MIME detection, MD5/hash helpers,
 * and image-dimension reading.
 */
class FileInfo extends \SplFileInfo
{
    private string $name      = '';
    private string $extension = '';
    private ?string $mimetype  = null;

    public function __construct(string $filePathname, ?string $newName = null)
    {
        parent::__construct($filePathname);

        $this->extension = strtolower(parent::getExtension());
        $rawName         = $newName ?? parent::getBasename('.' . parent::getExtension());
        $this->name      = $this->sanitizeName($rawName);
    }

    // ------------------------------------------------------------------
    // Name
    // ------------------------------------------------------------------

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $this->sanitizeName(basename($name));
    }

    // ------------------------------------------------------------------
    // Extension
    // ------------------------------------------------------------------

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = strtolower($extension);
    }

    // ------------------------------------------------------------------
    // Combined
    // ------------------------------------------------------------------

    public function getNameWithExtension(): string
    {
        return $this->name . '.' . $this->extension;
    }

    // ------------------------------------------------------------------
    // MIME type (lazy)
    // ------------------------------------------------------------------

    public function getMimetype(): string
    {
        if ($this->mimetype !== null) {
            return $this->mimetype;
        }

        $finfo = new \finfo(FILEINFO_MIME);
        $raw   = $finfo->file($this->getPathname());

        if ($raw === false) {
            $this->mimetype = '';
            return $this->mimetype;
        }

        $parts          = preg_split('/[;,]/', $raw);
        $this->mimetype = strtolower(trim((string) ($parts === false ? $raw : ($parts[0] ?? $raw))));

        return $this->mimetype;
    }

    // ------------------------------------------------------------------
    // Hashing
    // ------------------------------------------------------------------

    public function getMd5(): string
    {
        return $this->getHash('md5');
    }

    public function getHash(string $algorithm = 'md5'): string
    {
        $hash = hash_file($algorithm, $this->getPathname());

        return $hash !== false ? $hash : '';
    }

    // ------------------------------------------------------------------
    // Dimensions
    // ------------------------------------------------------------------

    /**
     * @return array{width: int, height: int}
     */
    public function getDimensions(): array
    {
        $size = @getimagesize($this->getPathname());

        if ($size === false) {
            return ['width' => 0, 'height' => 0];
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }

    // ------------------------------------------------------------------
    // Removal
    // ------------------------------------------------------------------

    public function remove(): void
    {
        if ($this->isFile()) {
            unlink($this->getPathname());
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function sanitizeName(string $name): string
    {
        $clean = (string) preg_replace('/[^a-zA-Z0-9._\-]/', '_', $name);

        return basename($clean);
    }
}
