<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\LenonLeiteBouncerIntegration;

class ConfigSupport extends LenonLeiteBouncerIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
