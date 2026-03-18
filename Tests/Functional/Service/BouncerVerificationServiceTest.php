<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Functional\Service;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Entity\BouncerRequest;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerVerificationService;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\ActivePluginTrait;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\HelperEntitiesTrait;
use Symfony\Component\HttpFoundation\Request;

class BouncerVerificationServiceTest extends MauticMysqlTestCase
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

    public function testSingleVerificationCreatesDashboardRequestRow(): void
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

        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(true);
        static::getContainer()->set(Config::class, $config);

        $verificationService = static::getContainer()->get(BouncerVerificationService::class);
        \assert($verificationService instanceof BouncerVerificationService);
        $verificationService->verifyLead($lead);

        /** @var list<BouncerRequest> $requests */
        $requests = $this->em->getRepository(BouncerRequest::class)->findBy([], ['dateAdded' => 'DESC']);

        self::assertNotEmpty($requests);
        self::assertSame('lead.single_check', $requests[0]->getSource());
        self::assertSame('deliverable', $requests[0]->getStatus());
        self::assertSame(1, $requests[0]->getQuantity());
        self::assertSame(1, $requests[0]->getProcessed());
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
