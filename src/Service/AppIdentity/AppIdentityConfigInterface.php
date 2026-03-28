<?php

declare(strict_types=1);

namespace Directive\Service\AppIdentity;

interface AppIdentityConfigInterface
{
    public function getAppCode(): string;
    public function getAppName(): string;
    public function getAppVersion(): string;
    public function getAppDescription(): string;
    public function getAppUrl(): string;
}
