<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Client;

interface BouncerClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function verify(string $email): array;

    /**
     * @param list<array{email:string, leadId:int}> $entries
     *
     * @return array<string, mixed>
     */
    public function createBatch(array $entries): array;

    /**
     * @return array<string, mixed>
     */
    public function getBatchStatus(string $batchId): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function getBatchResults(string $batchId): array;
}
