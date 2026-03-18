<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(private Config $config)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MENU => ['onBuildMenu', 9999],
        ];
    }

    public function onBuildMenu(MenuEvent $event): void
    {
        if ('main' !== $event->getType()) {
            return;
        }

        if ($this->config->isPublished() && $this->config->isEnabled()) {
            return;
        }

        $menuItems = $event->getMenuItems();
        $menuItems['children'] = $this->removeBouncerMenuItem($menuItems['children'] ?? []);
        $event->setMenuItems($menuItems);
    }

    /**
     * @param array<string, mixed> $items
     *
     * @return array<string, mixed>
     */
    private function removeBouncerMenuItem(array $items): array
    {
        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['id'] ?? null) === 'mautic_bouncer_dashboard') {
                unset($items[$key]);

                continue;
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $items[$key]['children'] = $this->removeBouncerMenuItem($item['children']);
            }
        }

        return $items;
    }
}
