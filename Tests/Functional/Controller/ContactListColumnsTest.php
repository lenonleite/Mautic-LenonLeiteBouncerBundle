<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\ActivePluginTrait;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\HelperEntitiesTrait;
use Symfony\Component\HttpFoundation\Request;

class ContactListColumnsTest extends MauticMysqlTestCase
{
    use ActivePluginTrait;
    use HelperEntitiesTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activePlugin();
        $this->useCleanupRollback = false;
        $this->setUpSymfony($this->configParams);
        $this->enableBouncerConfig();
    }

    public function testContactsListShowsBouncerScoreColumn(): void
    {
        $lead = $this->createLead('Alice', 'alice@example.com');

        static::getContainer()->set(BouncerClientInterface::class, new class() implements BouncerClientInterface {
            public function verify(string $email): array
            {
                return [
                    'status'   => 'deliverable',
                    'score'    => 95,
                    'reason'   => 'accepted_email',
                    'toxic'    => 'no',
                    'toxicity' => 0,
                    'provider' => 'google',
                ];
            }

            public function createBatch(array $entries): array
            {
                return [];
            }

            public function getBatchStatus(string $batchId): array
            {
                return [];
            }

            public function getBatchResults(string $batchId): array
            {
                return [];
            }
        });

        $this->client->request(Request::METHOD_GET, sprintf('/s/bouncer/lead/%d/check', $lead->getId()));
        $this->client->request(Request::METHOD_GET, '/s/contacts');

        $response = $this->client->getResponse();
        $content  = (string) $response->getContent();

        self::assertTrue($response->isOk());
        self::assertStringContainsString('Bouncer Score', $content);
        self::assertStringContainsString('95', $content);
        self::assertStringNotContainsString('>Bouncer<', $content);
    }

    private function enableBouncerConfig(): void
    {
        $crawler          = $this->client->request(Request::METHOD_GET, '/s/config/edit?tab=bouncerconfig');
        $configSaveButton = $crawler->selectButton('config[buttons][apply]');
        $configForm       = $configSaveButton->form();
        $data             = $configForm->getValues();

        $data['config[coreconfig][site_url]']               = 'https://mautic-community.local';
        $data['config[leadconfig][contact_columns]']        = ['name', 'email', 'id'];
        $data['config[coreconfig][do_not_track_ips]']       = "%ip1%\n%ip2%";
        $data['config[bouncerconfig][bouncer_active]']      = 1;
        $data['config[bouncerconfig][bouncer_api_key]']     = 'test-api-key';
        $data['config[bouncerconfig][bouncer_batch_size]']  = 100;
        $data['config[bouncerconfig][bouncer_sync_limit]']  = 20;

        $configForm->setValues($data);
        $this->client->submit($configForm);
    }
}
