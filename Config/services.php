<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerApiClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'rector.php',
        'tests/*',
        'vendor/*',
        'Entity/BouncerRequest.php',
        'EventListener/*',
    ];

    $services->load('MauticPlugin\\LenonLeiteBouncerBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->set(MauticPlugin\LenonLeiteBouncerBundle\EventListener\ConfigSubscriber::class)
        ->tag('kernel.event_subscriber');
    $services->set(MauticPlugin\LenonLeiteBouncerBundle\EventListener\LeadViewSubscriber::class)
        ->tag('kernel.event_subscriber');
    $services->set(MauticPlugin\LenonLeiteBouncerBundle\EventListener\LeadSubscriber::class)
        ->tag('kernel.event_subscriber');
    $services->set(MauticPlugin\LenonLeiteBouncerBundle\EventListener\MenuSubscriber::class)
        ->tag('kernel.event_subscriber');
    $services->set(MauticPlugin\LenonLeiteBouncerBundle\Doctrine\LeadAutoVerifySubscriber::class)
        ->tag('doctrine.event_subscriber');

    $services->set(BouncerClientInterface::class, BouncerApiClient::class);
};
