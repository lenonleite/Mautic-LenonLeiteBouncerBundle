<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadViewSubscriber implements EventSubscriberInterface
{
    public function __construct(private Config $config)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', 0],
        ];
    }

    public function onTemplateRender(CustomTemplateEvent $event): void
    {
        if (!$this->config->isPublished() || !$this->config->isEnabled()) {
            return;
        }

        if ('@MauticLead/Lead/lead.html.twig' === $event->getTemplate()) {
            $event->setTemplate('@LenonLeiteBouncer/Lead/lead.html.twig');

            return;
        }

        if ('@MauticLead/Lead/list.html.twig' === $event->getTemplate()) {
            $event->setTemplate('@LenonLeiteBouncer/Lead/list.html.twig');
        }
    }
}
