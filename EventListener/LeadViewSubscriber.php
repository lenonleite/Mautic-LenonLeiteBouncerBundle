<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomTemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadViewSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_TEMPLATE => ['onTemplateRender', 0],
        ];
    }

    public function onTemplateRender(CustomTemplateEvent $event): void
    {
        if ('@MauticLead/Lead/lead.html.twig' === $event->getTemplate()) {
            $event->setTemplate('@LenonLeiteBouncer/Lead/lead.html.twig');

            return;
        }

        if ('@MauticLead/Lead/list.html.twig' === $event->getTemplate()) {
            $event->setTemplate('@LenonLeiteBouncer/Lead/list.html.twig');
        }
    }
}
