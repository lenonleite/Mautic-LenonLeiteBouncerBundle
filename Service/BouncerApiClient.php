<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Exception\BouncerApiException;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BouncerApiClient implements BouncerClientInterface
{
    private const BASE_URI = 'https://api.usebouncer.com/v1.1';

    public function __construct(
        private HttpClientInterface $httpClient,
        private Config $config,
        private TranslatorInterface $translator,
    ) {
    }

    public function verify(string $email): array
    {
        return $this->request('GET', '/email/verify', [
            'query' => ['email' => $email],
        ]);
    }

    public function createBatch(array $entries): array
    {
        return $this->request('POST', '/email/verify/batch', [
            'json' => array_map(
                static fn (array $entry): array => ['email' => $entry['email']],
                $entries
            ),
        ]);
    }

    public function getBatchStatus(string $batchId): array
    {
        return $this->request('GET', '/email/verify/batch/'.$batchId, [
            'query' => ['with-stats' => 'true'],
        ]);
    }

    public function getBatchResults(string $batchId): array
    {
        $response = $this->request('GET', '/email/verify/batch/'.$batchId.'/download');

        if (isset($response['results']) && is_array($response['results'])) {
            return $response['results'];
        }

        if (isset($response['data']) && is_array($response['data'])) {
            return array_is_list($response['data']) ? $response['data'] : [];
        }

        /** @var mixed $decodedResponse */
        $decodedResponse = $response;

        return is_array($decodedResponse) && array_is_list($decodedResponse) ? $decodedResponse : [];
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options = []): array
    {
        $apiKey = $this->config->getApiKey();
        if ('' === $apiKey) {
            throw new BouncerApiException($this->translator->trans('lenonleitebouncer.api.error.api_key_missing'));
        }

        $headers = [
            'x-api-key'    => $apiKey,
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $response = $this->httpClient->request($method, self::BASE_URI.$path, $options + ['headers' => $headers]);
        $status   = $response->getStatusCode();

        if ($status >= 400) {
            $body = $response->getContent(false);

            throw new BouncerApiException($this->translator->trans('lenonleitebouncer.api.error.request_failed', ['%status%' => $status, '%body%'   => '' !== trim($body) ? $body : 'empty response body']));
        }

        $payload = json_decode($response->getContent(false), true);
        if (!is_array($payload)) {
            throw new BouncerApiException($this->translator->trans('lenonleitebouncer.api.error.unexpected_response'));
        }

        return $payload;
    }
}
