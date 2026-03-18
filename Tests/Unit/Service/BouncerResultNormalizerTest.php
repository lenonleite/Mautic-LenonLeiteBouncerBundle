<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\Service;

use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerResultNormalizer;
use PHPUnit\Framework\TestCase;

class BouncerResultNormalizerTest extends TestCase
{
    public function testNormalizeMapsExpectedFields(): void
    {
        $normalizer = new BouncerResultNormalizer();

        $result = $normalizer->normalize([
            'status'   => 'deliverable',
            'score'    => 0.87,
            'reason'   => 'accepted_email',
            'toxic'    => 1,
            'toxicity' => 3,
            'provider' => 'google',
        ]);

        self::assertSame('deliverable', $result['status']);
        self::assertSame(87, $result['score']);
        self::assertSame('accepted_email', $result['reason']);
        self::assertSame('yes', $result['toxic']);
        self::assertSame(3, $result['toxicity']);
        self::assertSame('google', $result['provider']);
        self::assertStringContainsString('"status": "deliverable"', $result['raw']);
    }

    public function testNormalizeMapsFalseyToxicValueToNo(): void
    {
        $normalizer = new BouncerResultNormalizer();

        $result = $normalizer->normalize([
            'result' => 'unknown',
            'score'  => 95,
            'toxic'  => false,
        ]);

        self::assertSame('unknown', $result['status']);
        self::assertSame(95, $result['score']);
        self::assertSame('no', $result['toxic']);
        self::assertNull($result['toxicity']);
    }
}
