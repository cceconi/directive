<?php

declare(strict_types=1);

namespace Directive\Http\Upload\Validators;

use Directive\Service\Security\Antivirus\AntivirusServiceInterface;
use Directive\Service\Security\Antivirus\Exception\AntivirusException;
use Directive\Http\Upload\FileInfo;
use Directive\Http\Upload\ValidatorException;
use Directive\Http\Upload\ValidatorInterface;

/**
 * Scans a file through the configured antivirus service.
 *
 * Strategy STRATEGY_BLOCK (default): throws on any infected/error result.
 * Strategy STRATEGY_PASS:            logs but lets the file through on error; blocks only
 *                                    when definitely infected.
 */
final class Antivirus implements ValidatorInterface
{
    public const string STRATEGY_BLOCK = 'block';
    public const string STRATEGY_PASS  = 'pass';

    public function __construct(
        private readonly AntivirusServiceInterface $avService,
        private readonly string $strategy = self::STRATEGY_BLOCK,
    ) {}

    public function validate(FileInfo $fileInfo): void
    {
        $originalPerm = fileperms($fileInfo->getPathname());
        chmod($fileInfo->getPathname(), 0o664);

        try {
            $av       = $this->avService->create();
            $av->ping();
            $analysis = $av->scan([$fileInfo->getPathname()]);

            foreach ($analysis->all() as $result) {
                if ($result->isInfected()) {
                    $this->avService->getLogger()->warning(
                        sprintf('Infected file detected: %s', $result->getFilename()),
                    );
                    throw new ValidatorException('Le fichier est infecté.', $fileInfo);
                }

                if ($result->isError()) {
                    $this->avService->getLogger()->error(
                        sprintf(
                            'AV scan error on %s: %s',
                            $result->getFilename(),
                            $result->getMessage() ?? '',
                        ),
                    );

                    if ($this->strategy === self::STRATEGY_BLOCK) {
                        throw new ValidatorException(
                            "Le fichier n'a pas pu être analysé, merci de ressayer plus tard.",
                            $fileInfo,
                        );
                    }
                }
            }
        } catch (AntivirusException $e) {
            $this->avService->getLogger()->error(sprintf('Antivirus error: %s', $e->getMessage()));

            if ($this->strategy === self::STRATEGY_BLOCK) {
                throw new ValidatorException(
                    "Le fichier n'a pas pu être analysé, merci de ressayer plus tard.",
                    $fileInfo,
                    0,
                    $e,
                );
            }
        } finally {
            if ($originalPerm !== false) {
                chmod($fileInfo->getPathname(), $originalPerm);
            }
        }
    }
}
