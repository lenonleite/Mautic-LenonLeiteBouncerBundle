<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\ActivePluginTrait;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ConfigPageTest extends MauticMysqlTestCase
{
    use ActivePluginTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activePlugin();
        $this->useCleanupRollback = false;
        $this->setUpSymfony($this->configParams);
    }

    public function testBouncerConfigPageShowsPanelAndReferralButton(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=bouncerconfig');
        $response = $this->client->getResponse();
        $content = (string) $response->getContent();

        Assert::assertTrue($response->isOk());
        Assert::assertStringContainsString('Bouncer Settings', $content);
        Assert::assertStringContainsString('Buy Credits with Referral', $content);
        Assert::assertStringContainsString('https://withlove.usebouncer.com/ioxhfxgs6zii', $content);
        Assert::assertCount(1, $crawler->filter('button[name="config[buttons][apply]"]'));
    }
}
