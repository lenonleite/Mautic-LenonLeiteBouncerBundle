<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\ActivePluginTrait;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\HelperEntitiesTrait;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class LeadViewButtonsTest extends MauticMysqlTestCase
{
    use ActivePluginTrait;
    use HelperEntitiesTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activePlugin();
        $this->useCleanupRollback = false;
        $this->setUpSymfony($this->configParams);
    }

    public function testLeadViewShowsBouncerButtonsForLeadWithEmail(): void
    {
        $lead = $this->createLead('Alice', 'alice@example.com');

        $this->client->request(Request::METHOD_GET, sprintf('/s/contacts/view/%d', $lead->getId()));
        $response = $this->client->getResponse();
        $content  = (string) $response->getContent();

        Assert::assertTrue($response->isOk());
        Assert::assertStringContainsString('Check with Bouncer', $content);
        Assert::assertStringContainsString('View Bouncer Details', $content);
        Assert::assertStringContainsString(sprintf('/s/bouncer/lead/%d/check', $lead->getId()), $content);
        Assert::assertStringContainsString(sprintf('/s/bouncer/lead/%d/details', $lead->getId()), $content);
    }
}
