<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Entity\BouncerRequest;

class BouncerRequestStore
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return list<BouncerRequest>
     */
    public function getRecent(int $page = 1, int $limit = 25): array
    {
        $offset = max(0, ($page - 1) * $limit);

        /** @var list<BouncerRequest> $requests */
        $requests = $this->entityManager->getRepository(BouncerRequest::class)->findBy([], ['dateAdded' => 'DESC'], $limit, $offset);

        return $requests;
    }

    /**
     * @return list<BouncerRequest>
     */
    public function findPending(int $limit): array
    {
        /** @var list<BouncerRequest> $requests */
        $requests = $this->entityManager->getRepository(BouncerRequest::class)->findBy(
            ['status' => ['pending', 'processing', 'queued']],
            ['dateAdded' => 'ASC'],
            $limit
        );

        return $requests;
    }

    /**
     * @return array{requests:int, emails:int, credits:float}
     */
    public function getUsageTotals(): array
    {
        /** @var list<BouncerRequest> $requests */
        $requests = $this->entityManager->getRepository(BouncerRequest::class)->findAll();

        $emails  = 0;
        $credits = 0.0;
        foreach ($requests as $request) {
            $emails += $request->getQuantity();
            $credits += $request->getCreditsUsed();
        }

        return [
            'requests' => count($requests),
            'emails'   => $emails,
            'credits'  => $credits,
        ];
    }

    /**
     * @param array{status:string, score:int|null, reason:string, toxic:string, toxicity:int|null, provider:string, raw:string} $normalized
     */
    public function recordSingleCheck(Lead $lead, array $normalized): void
    {
        $request = new BouncerRequest();
        $request->setBatchId(sprintf('single-%d-%d', (int) $lead->getId(), time()));
        $request->setStatus($normalized['status']);
        $request->setQuantity(1);
        $request->setProcessed(1);
        $request->setCreditsUsed(1.0);
        $request->setPayloadJson($normalized['raw']);
        $request->setSource('lead.single_check');
        $request->setDateCompleted(new \DateTimeImmutable());

        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }
}
