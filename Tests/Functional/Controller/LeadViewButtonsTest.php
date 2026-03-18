<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Event\CustomTemplateEvent;
use MauticPlugin\LenonLeiteBouncerBundle\EventListener\LeadViewSubscriber;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class LeadViewButtonsTest extends TestCase
{
    public function testLeadViewUsesBouncerTemplateWhenPluginIsActive(): void
    {
        $subscriber = new LeadViewSubscriber($this->createEnabledConfigMock());
        $event      = new CustomTemplateEvent(new Request(), '@MauticLead/Lead/lead.html.twig');

        $subscriber->onTemplateRender($event);

        self::assertSame('@LenonLeiteBouncer/Lead/lead.html.twig', $event->getTemplate());

        $template = file_get_contents(__DIR__.'/../../../Resources/views/Lead/lead.html.twig');
        self::assertIsString($template);
        self::assertStringContainsString('lenonleitebouncer.lead.action.check', $template);
        self::assertStringContainsString('lenonleitebouncer.lead.action.details', $template);
        self::assertStringContainsString('mautic_bouncer_check_lead', $template);
        self::assertStringContainsString('mautic_bouncer_lead_details', $template);
    }

    public function testLeadViewKeepsDefaultTemplateWhenPluginIsInactive(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isPublished')->willReturn(false);
        $config->method('isEnabled')->willReturn(false);

        $subscriber = new LeadViewSubscriber($config);
        $event      = new CustomTemplateEvent(new Request(), '@MauticLead/Lead/lead.html.twig');

        $subscriber->onTemplateRender($event);

        self::assertSame('@MauticLead/Lead/lead.html.twig', $event->getTemplate());
    }

    private function createEnabledConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('isPublished')->willReturn(true);
        $config->method('isEnabled')->willReturn(true);

        return $config;
    }
}
