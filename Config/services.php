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
    ];

    $services->load('MauticPlugin\\LenonLeiteBouncerBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->set(BouncerClientInterface::class, BouncerApiClient::class);
};
