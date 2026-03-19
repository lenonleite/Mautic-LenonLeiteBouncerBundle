<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\Service;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerFieldWriter;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerRequestStore;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerResultNormalizer;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerVerificationService;
use PHPUnit\Framework\TestCase;

class BouncerVerificationServiceTest extends TestCase
{
    public function testSingleVerificationCreatesDashboardRequestRow(): void
    {
        $lead = new Lead();
        $lead->setId(15);
        $lead->setEmail('alice@example.com');

        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(true);

        $client = $this->createMock(BouncerClientInterface::class);
        $client->expects(self::once())
            ->method('verify')
            ->with('alice@example.com')
            ->willReturn([
                'status'   => 'deliverable',
                'score'    => 95,
                'reason'   => 'accepted_email',
                'toxic'    => 'no',
                'toxicity' => 0,
                'provider' => 'google',
            ]);

        $normalized = [
            'status'   => 'deliverable',
            'score'    => 95,
            'reason'   => 'accepted_email',
            'toxic'    => 'no',
            'toxicity' => 0,
            'provider' => 'google',
            'raw'      => '{}',
        ];

        $normalizer = $this->createMock(BouncerResultNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->willReturn($normalized);

        $fieldWriter = $this->createMock(BouncerFieldWriter::class);
        $fieldWriter->expects(self::once())
            ->method('write')
            ->with($lead, $normalized)
            ->willReturn(true);

        $requestStore = $this->createMock(BouncerRequestStore::class);
        $requestStore->expects(self::once())
            ->method('recordSingleCheck')
            ->with($lead, $normalized);

        $service = new BouncerVerificationService(
            $config,
            $client,
            $normalizer,
            $fieldWriter,
            $requestStore,
        );

        self::assertSame($normalized, $service->verifyLead($lead));
    }
}
