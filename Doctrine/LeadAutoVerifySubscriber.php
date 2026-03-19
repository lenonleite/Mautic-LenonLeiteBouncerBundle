<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerVerificationService;

class LeadAutoVerifySubscriber implements EventSubscriber
{
    /** @var array<int, Lead> */
    private array $queuedLeads = [];

    private bool $processing = false;

    public function __construct(
        private Config $config,
        private BouncerVerificationService $verificationService,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postFlush,
        ];
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();
        if (!$entity instanceof Lead || !$this->shouldRun() || !$this->hasEmail($entity)) {
            return;
        }

        $this->queueLead($entity);
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        if (!$entity instanceof Lead || !$this->shouldRun() || !$this->hasEmail($entity)) {
            return;
        }

        $changes = $entity->getChanges();
        if ($this->hasBouncerFieldChanges($changes) || !$this->emailWasAddedOrChanged($changes)) {
            return;
        }

        $this->queueLead($entity);
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ($this->processing || [] === $this->queuedLeads) {
            return;
        }

        $this->processing  = true;
        $queuedLeads       = $this->queuedLeads;
        $this->queuedLeads = [];

        try {
            foreach ($queuedLeads as $lead) {
                $this->verificationService->verifyLead($lead);
            }
        } finally {
            $this->processing = false;
        }
    }

    private function shouldRun(): bool
    {
        return $this->config->isEnabled() && $this->config->shouldCheckOnCreate();
    }

    private function hasEmail(Lead $lead): bool
    {
        return null !== $lead->getEmail() && '' !== trim($lead->getEmail());
    }

    private function queueLead(Lead $lead): void
    {
        $this->queuedLeads[$lead->getId()] = $lead;
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function emailWasAddedOrChanged(array $changes): bool
    {
        $emailChange = $changes['fields']['email'] ?? $changes['email'] ?? null;

        return is_array($emailChange) && array_key_exists(1, $emailChange);
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function hasBouncerFieldChanges(array $changes): bool
    {
        $fieldChanges = $changes['fields'] ?? [];
        if (!is_array($fieldChanges)) {
            return false;
        }

        foreach (array_keys($fieldChanges) as $alias) {
            if (is_string($alias) && str_starts_with($alias, 'bouncer_')) {
                return true;
            }
        }

        return false;
    }
}
