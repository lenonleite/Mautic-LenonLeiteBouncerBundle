<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Integration;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration as IntegrationEntity;

class Config
{
    public function __construct(
        private IntegrationsHelper $integrationsHelper,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function isPublished(): bool
    {
        try {
            return (bool) $this->getIntegrationEntity()->getIsPublished();
        } catch (IntegrationNotFoundException) {
            return false;
        }
    }

    public function isEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get('bouncer_active')
            && '' !== $this->getApiKey();
    }

    public function getApiKey(): string
    {
        return trim((string) $this->coreParametersHelper->get('bouncer_api_key'));
    }

    public function getPartnerUrl(): string
    {
        return trim((string) $this->coreParametersHelper->get('bouncer_partner_url'));
    }

    public function shouldCheckOnCreate(): bool
    {
        return (bool) $this->coreParametersHelper->get('bouncer_check_on_create');
    }

    public function getBatchSize(): int
    {
        return max(1, (int) $this->coreParametersHelper->get('bouncer_batch_size'));
    }

    public function getSyncLimit(): int
    {
        return max(1, (int) $this->coreParametersHelper->get('bouncer_sync_limit'));
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): IntegrationEntity
    {
        $integrationObject = $this->integrationsHelper->getIntegration(LenonLeiteBouncerIntegration::INTEGRATION_NAME);

        return $integrationObject->getIntegrationConfiguration();
    }
}
