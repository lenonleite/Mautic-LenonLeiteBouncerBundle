<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Client\BouncerClientInterface;
use MauticPlugin\LenonLeiteBouncerBundle\Entity\BouncerRequest;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;

class BouncerBatchService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Config $config,
        private BouncerClientInterface $client,
        private BouncerRequestStore $requestStore,
        private BouncerResultNormalizer $normalizer,
        private BouncerFieldWriter $fieldWriter,
    ) {
    }

    /**
     * @return array{submitted:int, batch_id:string, request_id:int|null}
     */
    public function createBatch(int $limit, int $minId = 0): array
    {
        $leads = $this->entityManager->createQueryBuilder()
            ->select('l')
            ->from(Lead::class, 'l')
            ->where('l.id > :minId')
            ->andWhere('l.email IS NOT NULL')
            ->andWhere('l.email != :emptyEmail')
            ->setParameter('minId', $minId)
            ->setParameter('emptyEmail', '')
            ->orderBy('l.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $entries = [];
        foreach ($leads as $lead) {
            if (!$lead instanceof Lead) {
                continue;
            }

            $entries[] = [
                'leadId' => (int) $lead->getId(),
                'email'  => (string) $lead->getEmail(),
            ];
        }

        if ([] === $entries) {
            return [
                'submitted'  => 0,
                'batch_id'   => '',
                'request_id' => null,
            ];
        }

        $payload = $this->client->createBatch($entries);
        $request = new BouncerRequest();
        $request->setBatchId((string) ($payload['batchId'] ?? $payload['batch_id'] ?? $payload['id'] ?? ''));
        $request->setStatus((string) ($payload['status'] ?? 'queued'));
        $request->setQuantity((int) ($payload['quantity'] ?? count($entries)));
        $request->setProcessed((int) ($payload['processed'] ?? 0));
        $request->setCreditsUsed((float) ($payload['credits'] ?? $payload['credits_used'] ?? 0));
        $request->setPayloadJson((string) json_encode($entries, JSON_UNESCAPED_SLASHES));
        $request->setSource('command.batch_create');

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return [
            'submitted'  => count($entries),
            'batch_id'   => $request->getBatchId(),
            'request_id' => $request->getId(),
        ];
    }

    /**
     * @return array{requests:int, processed:int, updated:int}
     */
    public function syncPendingRequests(?int $limit = null): array
    {
        $requests = $this->requestStore->findPending($limit ?? $this->config->getSyncLimit());

        $processedRequests = 0;
        $processedLeads    = 0;
        $updatedLeads      = 0;

        foreach ($requests as $request) {
            ++$processedRequests;
            $result         = $this->syncRequest($request);
            $processedLeads += $result['processed'];
            $updatedLeads += $result['updated'];
        }

        return [
            'requests'  => $processedRequests,
            'processed' => $processedLeads,
            'updated'   => $updatedLeads,
        ];
    }

    /**
     * @return array{processed:int, updated:int}
     */
    public function syncRequest(BouncerRequest $request): array
    {
        $statusPayload = $this->client->getBatchStatus($request->getBatchId());
        $request->setStatus($this->normalizeBatchStatus((string) ($statusPayload['status'] ?? $request->getStatus())));
        $request->setProcessed((int) ($statusPayload['processed'] ?? $request->getProcessed()));
        $request->setCreditsUsed((float) ($statusPayload['credits'] ?? $statusPayload['credits_used'] ?? $request->getCreditsUsed()));

        $processed = 0;
        $updated   = 0;
        if ('completed' === $request->getStatus()) {
            $entries  = $this->decodeEntries($request->getPayloadJson());
            $byEmail  = [];
            $byLeadId = [];
            foreach ($entries as $entry) {
                $leadId                                                   = (int) $entry['leadId'];
                $byEmail[$this->normalizeEmail((string) $entry['email'])] = $leadId;
                $byLeadId[$leadId]                                        = $leadId;
            }

            foreach ($this->client->getBatchResults($request->getBatchId()) as $row) {
                if (!is_array($row)) {
                    continue;
                }

                ++$processed;
                $payload = $this->extractResultPayload($row);
                $leadId  = $this->extractLeadId($payload, $byLeadId);
                $email   = $this->extractEmail($payload);

                if (null === $leadId && ('' === $email || !isset($byEmail[$email]))) {
                    continue;
                }

                $lead = $this->entityManager->getRepository(Lead::class)->find($leadId ?? $byEmail[$email]);
                if (!$lead instanceof Lead) {
                    continue;
                }

                $normalized = $this->normalizer->normalize($payload);
                if ($this->fieldWriter->write($lead, $normalized)) {
                    ++$updated;
                }
            }

            $request->setDateCompleted(new \DateTimeImmutable());
        }

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return [
            'processed' => $processed,
            'updated'   => $updated,
        ];
    }

    /**
     * @return list<array{leadId:int, email:string}>
     */
    private function decodeEntries(?string $payloadJson): array
    {
        if (null === $payloadJson || '' === trim($payloadJson)) {
            return [];
        }

        $decoded = json_decode($payloadJson, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeBatchStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'ready', 'done', 'finished', 'complete' => 'completed',
            default => '' !== $normalized ? $normalized : 'pending',
        };
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function extractResultPayload(array $row): array
    {
        foreach (['result', 'email', 'verification'] as $nestedKey) {
            if (isset($row[$nestedKey]) && is_array($row[$nestedKey])) {
                return $row + $row[$nestedKey];
            }
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractEmail(array $payload): string
    {
        foreach (['email', 'address'] as $key) {
            if (isset($payload[$key]) && is_string($payload[$key])) {
                return $this->normalizeEmail($payload[$key]);
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, int>      $knownLeadIds
     */
    private function extractLeadId(array $payload, array $knownLeadIds): ?int
    {
        foreach (['leadId', 'lead_id', 'id'] as $key) {
            if (!isset($payload[$key]) || !is_numeric($payload[$key])) {
                continue;
            }

            $leadId = (int) $payload[$key];
            if (isset($knownLeadIds[$leadId])) {
                return $leadId;
            }
        }

        return null;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
