<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\EventListener;

use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerVerificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Config $config,
        private BouncerVerificationService $verificationService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_POST_SAVE => ['onLeadPostSave', 0],
        ];
    }

    public function onLeadPostSave(LeadEvent $event): void
    {
        if (!$event->isNew() || !$this->config->isEnabled() || !$this->config->shouldCheckOnCreate()) {
            return;
        }

        $lead = $event->getLead();
        if (null === $lead->getEmail() || '' === trim($lead->getEmail())) {
            return;
        }

        $this->verificationService->verifyLead($lead);
    }
}
