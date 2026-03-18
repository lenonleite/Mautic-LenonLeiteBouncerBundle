<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Command\BouncerBatchSyncCommand;
use MauticPlugin\LenonLeiteBouncerBundle\Entity\BouncerRequest;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\ActivePluginTrait;
use MauticPlugin\LenonLeiteBouncerBundle\Tests\Traits\HelperEntitiesTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;

class BouncerBatchSyncCommandTest extends MauticMysqlTestCase
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

    public function testSyncUpdatesLeadFieldsFromCompletedBatch(): void
    {
        $this->addField('text', 'bouncer_status', 'Bouncer Status');
        $this->addField('number', 'bouncer_score', 'Bouncer Score');
        $this->addField('textarea', 'bouncer_raw_response', 'Bouncer Raw Response');
        $this->addField('datetime', 'bouncer_last_checked_at', 'Bouncer Last Checked');

        $lead = $this->createLead('Alice', 'alice@example.com');

        $request = new BouncerRequest();
        $request->setBatchId('batch-1');
        $request->setStatus('pending');
        $request->setQuantity(1);
        $request->setPayloadJson((string) json_encode([[
            'leadId' => $lead->getId(),
            'email'  => $lead->getEmail(),
        ]], JSON_UNESCAPED_SLASHES));
        $this->em->persist($request);
        $this->em->flush();

        static::getContainer()->set(BouncerClientInterface::class, new class() implements BouncerClientInterface {
            public function verify(string $email): array
            {
                return [];
            }

            public function createBatch(array $entries): array
            {
                return [];
            }

            public function getBatchStatus(string $batchId): array
            {
                return [
                    'status'       => 'completed',
                    'processed'    => 1,
                    'credits_used' => 1,
                ];
            }

            public function getBatchResults(string $batchId): array
            {
                return [[
                    'email'  => 'alice@example.com',
                    'status' => 'deliverable',
                    'score'  => 0.95,
                ]];
            }
        });

        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('getSyncLimit')->willReturn(20);
        static::getContainer()->set(Config::class, $config);

        $command = static::getContainer()->get(BouncerBatchSyncCommand::class);
        assert($command instanceof BouncerBatchSyncCommand);
        $result = new CommandTester($command);
        $result->execute([]);

        self::assertStringContainsString('Requests synced: 1', $result->getDisplay());
        self::assertStringContainsString('Leads updated: 1', $result->getDisplay());

        $this->em->clear();

        $reloadedLead = $this->em->getRepository(Lead::class)->find($lead->getId());
        assert($reloadedLead instanceof Lead);
        $reloadedLead->setFields($this->em->getRepository(Lead::class)->getFieldValues($reloadedLead->getId()));

        self::assertSame('deliverable', $reloadedLead->getFieldValue('bouncer_status'));
        self::assertEquals(95, $reloadedLead->getFieldValue('bouncer_score'));
        self::assertStringContainsString('"status": "deliverable"', (string) $reloadedLead->getFieldValue('bouncer_raw_response'));
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
