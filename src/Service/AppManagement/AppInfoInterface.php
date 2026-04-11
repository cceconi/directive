<?php

declare(strict_types=1);

namespace Directive\Service\AppManagement;

interface AppInfoInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function getCommitId(): string;
    public function getBranch(): string;
    public function getTag(): string;
    public function getBuildNumber(): string;
    public function getBuiltAt(): string;
    public function getBuiltBy(): string;
}
