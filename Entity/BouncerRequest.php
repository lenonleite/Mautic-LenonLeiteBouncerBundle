<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class BouncerRequest
{
    private ?int $id              = null;
    private ?string $batchId      = '';
    private string $status        = 'pending';
    private int $quantity         = 0;
    private int $processed        = 0;
    private float $creditsUsed    = 0.0;
    private ?string $payloadJson  = null;
    private ?string $errorMessage = null;
    private string $source        = 'command';
    private \DateTimeInterface $dateAdded;
    private \DateTimeInterface $dateModified;
    private ?\DateTimeInterface $dateCompleted = null;

    public function __construct()
    {
        $now                = new \DateTimeImmutable();
        $this->dateAdded    = $now;
        $this->dateModified = $now;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('plugin_bouncer_requests');

        $builder->addId();
        $builder->addNamedField('batchId', Types::STRING, 'batch_id');
        $builder->addNamedField('status', Types::STRING, 'status');
        $builder->addNamedField('quantity', Types::INTEGER, 'quantity');
        $builder->addNamedField('processed', Types::INTEGER, 'processed');
        $builder->addNamedField('creditsUsed', Types::FLOAT, 'credits_used');
        $builder->addNullableField('payloadJson', Types::TEXT, 'payload_json');
        $builder->addNullableField('errorMessage', Types::TEXT, 'error_message');
        $builder->addNamedField('source', Types::STRING, 'source');
        $builder->createField('dateAdded', Types::DATETIME_MUTABLE)
            ->columnName('date_added')
            ->build();
        $builder->createField('dateModified', Types::DATETIME_MUTABLE)
            ->columnName('date_modified')
            ->build();
        $builder->createField('dateCompleted', Types::DATETIME_MUTABLE)
            ->columnName('date_completed')
            ->nullable()
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBatchId(): string
    {
        return $this->batchId ?? '';
    }

    public function setBatchId(string $batchId): void
    {
        $this->batchId = $batchId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->touch();
    }

    public function getProcessed(): int
    {
        return $this->processed;
    }

    public function setProcessed(int $processed): void
    {
        $this->processed = $processed;
        $this->touch();
    }

    public function getCreditsUsed(): float
    {
        return $this->creditsUsed;
    }

    public function setCreditsUsed(float $creditsUsed): void
    {
        $this->creditsUsed = $creditsUsed;
        $this->touch();
    }

    public function getPayloadJson(): ?string
    {
        return $this->payloadJson;
    }

    public function setPayloadJson(?string $payloadJson): void
    {
        $this->payloadJson = $payloadJson;
        $this->touch();
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
        $this->touch();
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
        $this->touch();
    }

    public function getDateAdded(): \DateTimeInterface
    {
        return $this->dateAdded;
    }

    public function getDateModified(): \DateTimeInterface
    {
        return $this->dateModified;
    }

    public function getDateCompleted(): ?\DateTimeInterface
    {
        return $this->dateCompleted;
    }

    public function setDateCompleted(?\DateTimeInterface $dateCompleted): void
    {
        $this->dateCompleted = $dateCompleted;
        $this->touch();
    }

    private function touch(): void
    {
        $this->dateModified = new \DateTimeImmutable();
    }
}
