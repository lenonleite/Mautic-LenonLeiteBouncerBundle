<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Model\LeadModel;

class BouncerFieldWriter
{
    private const FIELD_MAP = [
        'bouncer_status'          => 'status',
        'bouncer_score'           => 'score',
        'bouncer_reason'          => 'reason',
        'bouncer_toxic'           => 'toxic',
        'bouncer_toxicity'        => 'toxicity',
        'bouncer_provider'        => 'provider',
        'bouncer_raw_response'    => 'raw',
        'bouncer_last_checked_at' => '_timestamp',
    ];

    /** @var array<string, bool> */
    private array $fieldExistsCache = [];

    public function __construct(
        private LeadFieldRepository $leadFieldRepository,
        private LeadModel $leadModel,
        private BouncerFieldSetup $fieldSetup,
    ) {
    }

    /**
     * @param array{status:string, score:int|null, reason:string, toxic:string, toxicity:int|null, provider:string, raw:string} $normalized
     */
    public function write(Lead $lead, array $normalized): bool
    {
        $this->fieldSetup->ensureFieldsExist();
        $lead->setFields($this->leadModel->getRepository()->getFieldValues($lead->getId()));

        $hasUpdates = false;
        $timestamp  = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        foreach (self::FIELD_MAP as $fieldAlias => $payloadKey) {
            if (!$this->fieldExists($fieldAlias)) {
                continue;
            }

            $newValue = '_timestamp' === $payloadKey ? $timestamp : $normalized[$payloadKey];
            $oldValue = $lead->getFieldValue($fieldAlias);
            $lead->addUpdatedField($fieldAlias, $newValue, is_scalar($oldValue) ? (string) $oldValue : '');
            $hasUpdates = true;
        }

        if (!$hasUpdates) {
            return false;
        }

        $this->leadModel->saveEntity($lead);

        return true;
    }

    private function fieldExists(string $alias): bool
    {
        if (array_key_exists($alias, $this->fieldExistsCache)) {
            return $this->fieldExistsCache[$alias];
        }

        $field                          = $this->leadFieldRepository->findOneBy(['alias' => $alias]);
        $this->fieldExistsCache[$alias] = $field instanceof LeadField;

        return $this->fieldExistsCache[$alias];
    }
}
