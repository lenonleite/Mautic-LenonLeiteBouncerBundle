<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\Service;

use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class BouncerApiClientTest extends TestCase
{
    public function testCreateBatchSendsDocumentedArrayPayload(): void
    {
        $capturedMethod  = null;
        $capturedUrl     = null;
        $capturedOptions = [];

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedMethod, &$capturedUrl, &$capturedOptions) {
            $capturedMethod  = $method;
            $capturedUrl     = $url;
            $capturedOptions = $options;

            return new MockResponse(json_encode([
                'batchId'  => 'batch-123',
                'status'   => 'queued',
                'quantity' => 2,
                'credits'  => 2,
            ], JSON_THROW_ON_ERROR));
        });

        $client = new BouncerApiClient(
            $httpClient,
            $this->createConfigMock(),
            $this->createTranslator(),
        );

        $response = $client->createBatch([
            ['leadId' => 1, 'email' => 'alice@example.com'],
            ['leadId' => 2, 'email' => 'bob@example.com'],
        ]);

        self::assertSame('POST', $capturedMethod);
        self::assertSame('https://api.usebouncer.com/v1.1/email/verify/batch', $capturedUrl);
        self::assertJsonStringEqualsJsonString(
            '[{"email":"alice@example.com"},{"email":"bob@example.com"}]',
            (string) ($capturedOptions['body'] ?? '')
        );
        self::assertSame('batch-123', $response['batchId']);
    }

    private function createConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('getApiKey')->willReturn('test-api-key');

        return $config;
    }

    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static function (string $id, array $parameters = []): string {
                return strtr($id, $parameters);
            });

        return $translator;
    }
}
