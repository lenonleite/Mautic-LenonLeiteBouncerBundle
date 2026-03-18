<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class LenonLeiteBouncerIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const INTEGRATION_NAME = 'lenonleitebouncer';
    public const DISPLAY_NAME     = 'Bouncer Email Verification';

    public function getName(): string
    {
        return self::INTEGRATION_NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/LenonLeiteBouncerBundle/Assets/img/icon.svg';
    }
}
