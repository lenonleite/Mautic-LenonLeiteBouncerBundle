<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;

trait ActivePluginTrait
{
    private function activePlugin(bool $isPublished = true): void
    {
        $this->client->request('GET', '/s/plugins/reload');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $this->installPlugin('LenonLeiteBouncerBundle', $isPublished);
    }

    private function installPlugin(string $bundleName, bool $isPublished = true): void
    {
        $integrationName = str_replace('Bundle', '', $bundleName);
        $integration     = $this->em->getRepository(Integration::class)->findOneBy(['name' => $integrationName]);

        if (null === $integration) {
            $plugin = $this->em->getRepository(Plugin::class)->findOneBy(['bundle' => $bundleName]);
            if (null === $plugin) {
                $plugin = new Plugin();
                $plugin->setName($integrationName);
                $plugin->setBundle($bundleName);
                $plugin->setDescription('Bouncer email verification integration.');
                $plugin->setVersion('1.0.0');
                $plugin->setAuthor('Lenon Leite');
                $this->em->persist($plugin);
                $this->em->flush();
            }

            $integration = new Integration();
            $integration->setName($integrationName);
            $integration->setPlugin($plugin);
        }

        $integration->setIsPublished($isPublished);
        $this->em->persist($integration);
        $this->em->flush();
    }
}
