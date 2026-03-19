<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Entity\BouncerRequest;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerBatchService;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerFieldWriter;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerRequestStore;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerResultNormalizer;
use PHPUnit\Framework\TestCase;

class BouncerBatchServiceTest extends TestCase
{
    public function testSyncPendingRequestsUpdatesLeadFromCompletedBatch(): void
    {
        $lead    = new Lead();
        $lead->setId(15);
        $request = new BouncerRequest();
        $request->setBatchId('batch-1');
        $request->setStatus('pending');
        $request->setPayloadJson((string) json_encode([[
            'leadId' => 15,
            'email'  => 'alice@example.com',
        ]], JSON_UNESCAPED_SLASHES));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $leadRepo      = new class($lead) {
            public function __construct(private Lead $lead)
            {
            }

            public function find(int $id): ?Lead
            {
                return 15 === $id ? $this->lead : null;
            }
        };

        $entityManager->method('getRepository')->willReturn($leadRepo);
        $entityManager->expects(self::atLeastOnce())->method('persist');
        $entityManager->expects(self::atLeastOnce())->method('flush');

        $requestStore = $this->createMock(BouncerRequestStore::class);
        $requestStore->method('findPending')->with(20)->willReturn([$request]);

        $client = $this->createMock(BouncerClientInterface::class);
        $client->method('getBatchStatus')->with('batch-1')->willReturn([
            'status'       => 'completed',
            'processed'    => 1,
            'credits_used' => 1,
        ]);
        $client->method('getBatchResults')->with('batch-1')->willReturn([[
            'email'  => 'alice@example.com',
            'status' => 'deliverable',
            'score'  => 0.95,
        ]]);

        $normalizer = $this->createMock(BouncerResultNormalizer::class);
        $normalizer->expects(self::once())->method('normalize')->willReturn([
            'status'   => 'deliverable',
            'score'    => 95,
            'reason'   => 'accepted_email',
            'toxic'    => 'no',
            'toxicity' => 0,
            'provider' => 'google',
            'raw'      => '{}',
        ]);

        $fieldWriter = $this->createMock(BouncerFieldWriter::class);
        $fieldWriter->expects(self::once())->method('write')->with($lead)->willReturn(true);

        $service = $this->createBatchService(
            $entityManager,
            $this->createConfigMock(),
            $client,
            $requestStore,
            $normalizer,
            $fieldWriter,
        );

        $result = $service->syncPendingRequests();

        self::assertSame(['requests' => 1, 'processed' => 1, 'updated' => 1], $result);
        self::assertSame('completed', $request->getStatus());
        self::assertNotNull($request->getDateCompleted());
    }

    public function testSyncPendingRequestsHandlesNestedResultPayload(): void
    {
        $lead    = new Lead();
        $lead->setId(16);
        $request = new BouncerRequest();
        $request->setBatchId('batch-2');
        $request->setStatus('queued');
        $request->setPayloadJson((string) json_encode([[
            'leadId' => 16,
            'email'  => 'bob@example.com',
        ]], JSON_UNESCAPED_SLASHES));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $leadRepo      = new class($lead) {
            public function __construct(private Lead $lead)
            {
            }

            public function find(int $id): ?Lead
            {
                return 16 === $id ? $this->lead : null;
            }
        };

        $entityManager->method('getRepository')->willReturn($leadRepo);
        $entityManager->expects(self::atLeastOnce())->method('persist');
        $entityManager->expects(self::atLeastOnce())->method('flush');

        $requestStore = $this->createMock(BouncerRequestStore::class);
        $requestStore->method('findPending')->with(20)->willReturn([$request]);

        $client = $this->createMock(BouncerClientInterface::class);
        $client->method('getBatchStatus')->with('batch-2')->willReturn([
            'status'       => 'done',
            'processed'    => 1,
            'credits_used' => 1,
        ]);
        $client->method('getBatchResults')->with('batch-2')->willReturn([[
            'email'  => 'bob@example.com',
            'result' => [
                'status' => 'risky',
                'score'  => 0.61,
                'reason' => 'accept_all',
            ],
        ]]);

        $normalizer = $this->createMock(BouncerResultNormalizer::class);
        $normalizer->expects(self::once())->method('normalize')->willReturn([
            'status'   => 'risky',
            'score'    => 61,
            'reason'   => 'accept_all',
            'toxic'    => 'unknown',
            'toxicity' => null,
            'provider' => '',
            'raw'      => '{}',
        ]);

        $fieldWriter = $this->createMock(BouncerFieldWriter::class);
        $fieldWriter->expects(self::once())->method('write')->with($lead)->willReturn(true);

        $service = $this->createBatchService(
            $entityManager,
            $this->createConfigMock(),
            $client,
            $requestStore,
            $normalizer,
            $fieldWriter,
        );

        $result = $service->syncPendingRequests();

        self::assertSame(['requests' => 1, 'processed' => 1, 'updated' => 1], $result);
        self::assertSame('completed', $request->getStatus());
        self::assertNotNull($request->getDateCompleted());
    }

    private function createBatchService(
        EntityManagerInterface $entityManager,
        Config $config,
        BouncerClientInterface $client,
        BouncerRequestStore $requestStore,
        BouncerResultNormalizer $normalizer,
        BouncerFieldWriter $fieldWriter,
    ): BouncerBatchService {
        return new BouncerBatchService(
            $entityManager,
            $config,
            $client,
            $requestStore,
            $normalizer,
            $fieldWriter,
        );
    }

    private function createConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('getSyncLimit')->willReturn(20);

        return $config;
    }
}
