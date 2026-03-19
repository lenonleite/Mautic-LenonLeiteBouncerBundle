<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use MauticPlugin\LenonLeiteBouncerBundle\EventListener\ConfigSubscriber;
use PHPUnit\Framework\TestCase;

class ConfigSubscriberTest extends TestCase
{
    public function testOnConfigGenerateAddsBouncerConfigForm(): void
    {
        $event = $this->createMock(ConfigBuilderEvent::class);
        $event->expects(self::once())
            ->method('getParametersFromConfig')
            ->with('LenonLeiteBouncerBundle')
            ->willReturn(['bouncer_active' => true]);
        $event->expects(self::once())
            ->method('addForm')
            ->with(self::callback(function (array $form): bool {
                self::assertSame('LenonLeiteBouncerBundle', $form['bundle']);
                self::assertSame('bouncerconfig', $form['formAlias']);
                self::assertSame('@LenonLeiteBouncer/FormTheme/Config/_config_bouncerconfig_widget.html.twig', $form['formTheme']);

                return true;
            }));

        (new ConfigSubscriber())->onConfigGenerate($event);
    }

    public function testOnConfigBeforeSaveStoresBouncerConfigSection(): void
    {
        $event = $this->createMock(ConfigEvent::class);
        $event->expects(self::once())
            ->method('getConfig')
            ->with('bouncerconfig')
            ->willReturn(['bouncer_active' => true]);
        $event->expects(self::once())
            ->method('setConfig')
            ->with(['bouncer_active' => true], 'bouncerconfig');

        (new ConfigSubscriber())->onConfigBeforeSave($event);
    }

    public function testConfigTemplateContainsReferralButton(): void
    {
        $template = file_get_contents(__DIR__.'/../../../Resources/views/FormTheme/Config/_config_bouncerconfig_widget.html.twig');

        self::assertIsString($template);
        self::assertStringContainsString('mautic.config.tab.bouncerconfig', $template);
        self::assertStringContainsString('lenonleitebouncer.config.referral.button', $template);
        self::assertStringContainsString('https://withlove.usebouncer.com/ioxhfxgs6zii', $template);
    }
}
