<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Event\CustomTemplateEvent;
use MauticPlugin\LenonLeiteBouncerBundle\EventListener\LeadViewSubscriber;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ContactListColumnsTest extends TestCase
{
    public function testContactsListUsesBouncerTemplateWhenPluginIsActive(): void
    {
        $subscriber = new LeadViewSubscriber($this->createEnabledConfigMock());
        $event      = new CustomTemplateEvent(new Request(), '@MauticLead/Lead/list.html.twig');

        $subscriber->onTemplateRender($event);

        self::assertSame('@LenonLeiteBouncer/Lead/list.html.twig', $event->getTemplate());

        $template = file_get_contents(__DIR__.'/../../../Resources/views/Lead/_list.html.twig');
        self::assertIsString($template);
        self::assertStringContainsString("'bouncer_score'", $template);
        self::assertStringContainsString('lenonleitebouncer.contact_list.column.score', $template);
        self::assertStringNotContainsString("'bouncer':", $template);
    }

    public function testContactsListKeepsDefaultTemplateWhenPluginIsInactive(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isPublished')->willReturn(false);
        $config->method('isEnabled')->willReturn(false);

        $subscriber = new LeadViewSubscriber($config);
        $event      = new CustomTemplateEvent(new Request(), '@MauticLead/Lead/list.html.twig');

        $subscriber->onTemplateRender($event);

        self::assertSame('@MauticLead/Lead/list.html.twig', $event->getTemplate());
    }

    private function createEnabledConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('isPublished')->willReturn(true);
        $config->method('isEnabled')->willReturn(true);

        return $config;
    }
}
