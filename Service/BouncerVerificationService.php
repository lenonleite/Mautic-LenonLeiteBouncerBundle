<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;

class BouncerVerificationService
{
    public function __construct(
        private Config $config,
        private BouncerClientInterface $client,
        private BouncerResultNormalizer $normalizer,
        private BouncerFieldWriter $fieldWriter,
        private BouncerRequestStore $requestStore,
    ) {
    }

    /**
     * @return array{status:string, score:int|null, reason:string, toxic:string, toxicity:int|null, provider:string, raw:string}|null
     */
    public function verifyLead(Lead $lead): ?array
    {
        if (!$this->config->isEnabled() || null === $lead->getEmail() || '' === trim($lead->getEmail())) {
            return null;
        }

        $payload    = $this->client->verify($lead->getEmail());
        $normalized = $this->normalizer->normalize($payload);
        $this->fieldWriter->write($lead, $normalized);
        $this->requestStore->recordSingleCheck($lead, $normalized);

        return $normalized;
    }
}
