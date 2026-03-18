<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;

trait HelperEntitiesTrait
{
    private function createLead(string $name, string $email): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($name);
        $lead->setLastname($name.' lastname');
        $lead->setEmail($email);
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function addField(string $type, string $alias, string $name): LeadField
    {
        $field = new LeadField();
        $field->setType($type);
        $field->setObject('lead');
        $field->setAlias($alias);
        $field->setName($name);
        $field->setGroup('core');

        /** @var FieldModel $fieldModel */
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);

        return $field;
    }
}
