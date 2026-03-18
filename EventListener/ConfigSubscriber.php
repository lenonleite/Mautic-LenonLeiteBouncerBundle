<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use MauticPlugin\LenonLeiteBouncerBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigBeforeSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'LenonLeiteBouncerBundle',
            'formType'   => ConfigType::class,
            'formAlias'  => 'bouncerconfig',
            'formTheme'  => '@LenonLeiteBouncer/FormTheme/Config/_config_bouncerconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('LenonLeiteBouncerBundle'),
        ]);
    }

    public function onConfigBeforeSave(ConfigEvent $event): void
    {
        $event->setConfig($event->getConfig('bouncerconfig'), 'bouncerconfig');
    }
}
