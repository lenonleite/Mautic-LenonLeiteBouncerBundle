<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Model\FieldModel;

class BouncerFieldSetup
{
    private const FIELD_DEFINITIONS = [
        'bouncer_status' => [
            'type' => 'text',
            'name' => 'Bouncer Status',
        ],
        'bouncer_score' => [
            'type' => 'number',
            'name' => 'Bouncer Score',
        ],
        'bouncer_reason' => [
            'type' => 'text',
            'name' => 'Bouncer Reason',
        ],
        'bouncer_toxic' => [
            'type' => 'boolean',
            'name' => 'Bouncer Toxic',
        ],
        'bouncer_toxicity' => [
            'type' => 'text',
            'name' => 'Bouncer Toxicity',
        ],
        'bouncer_provider' => [
            'type' => 'text',
            'name' => 'Bouncer Provider',
        ],
        'bouncer_raw_response' => [
            'type' => 'textarea',
            'name' => 'Bouncer Raw Response',
        ],
        'bouncer_last_checked_at' => [
            'type' => 'datetime',
            'name' => 'Bouncer Last Checked At',
        ],
    ];

    private bool $initialized = false;

    public function __construct(
        private LeadFieldRepository $leadFieldRepository,
        private FieldModel $fieldModel,
    ) {
    }

    public function ensureFieldsExist(): void
    {
        if ($this->initialized) {
            return;
        }

        foreach (self::FIELD_DEFINITIONS as $alias => $definition) {
            $existing = $this->leadFieldRepository->findOneBy(['alias' => $alias]);
            if ($existing instanceof LeadField) {
                continue;
            }

            $field = new LeadField();
            $field->setType($definition['type']);
            $field->setObject('lead');
            $field->setAlias($alias);
            $field->setName($definition['name']);
            $field->setGroup('core');

            $this->fieldModel->saveEntity($field);
        }

        $this->initialized = true;
    }
}
