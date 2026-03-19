<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\LenonLeiteBouncerBundle\Doctrine\LeadAutoVerifySubscriber;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerVerificationService;
use PHPUnit\Framework\TestCase;

class LeadAutoVerifySubscriberTest extends TestCase
{
    public function testQueuesNewLeadAndVerifiesOnPostFlush(): void
    {
        $lead = new Lead();
        $lead->setId(15);
        $lead->setEmail('alice@example.com');

        $verificationService = $this->createMock(BouncerVerificationService::class);
        $verificationService->expects(self::once())
            ->method('verifyLead')
            ->with($lead);

        $subscriber = new LeadAutoVerifySubscriber($this->createEnabledConfigMock(), $verificationService);
        $subscriber->postPersist(new PostPersistEventArgs($lead, $this->createMock(EntityManagerInterface::class)));
        $subscriber->postFlush(new PostFlushEventArgs($this->createMock(EntityManagerInterface::class)));
    }

    public function testQueuesExistingLeadWhenEmailChanges(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getId')->willReturn(15);
        $lead->method('getEmail')->willReturn('alice@example.com');
        $lead->method('getChanges')->willReturn([
            'fields' => [
                'email' => ['', 'alice@example.com'],
            ],
        ]);

        $verificationService = $this->createMock(BouncerVerificationService::class);
        $verificationService->expects(self::once())
            ->method('verifyLead')
            ->with($lead);

        $subscriber = new LeadAutoVerifySubscriber($this->createEnabledConfigMock(), $verificationService);
        $subscriber->postUpdate(new PostUpdateEventArgs($lead, $this->createMock(EntityManagerInterface::class)));
        $subscriber->postFlush(new PostFlushEventArgs($this->createMock(EntityManagerInterface::class)));
    }

    public function testDoesNotQueueExistingLeadWhenEmailDidNotChange(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getId')->willReturn(15);
        $lead->method('getEmail')->willReturn('alice@example.com');
        $lead->method('getChanges')->willReturn([]);

        $verificationService = $this->createMock(BouncerVerificationService::class);
        $verificationService->expects(self::never())->method('verifyLead');

        $subscriber = new LeadAutoVerifySubscriber($this->createEnabledConfigMock(), $verificationService);
        $subscriber->postUpdate(new PostUpdateEventArgs($lead, $this->createMock(EntityManagerInterface::class)));
        $subscriber->postFlush(new PostFlushEventArgs($this->createMock(EntityManagerInterface::class)));
    }

    public function testDoesNotQueueWhenOnlyBouncerFieldsChange(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getId')->willReturn(15);
        $lead->method('getEmail')->willReturn('alice@example.com');
        $lead->method('getChanges')->willReturn([
            'fields' => [
                'email'                   => ['old@example.com', 'alice@example.com'],
                'bouncer_last_checked_at' => ['', '2026-01-01 00:00:00'],
            ],
        ]);

        $verificationService = $this->createMock(BouncerVerificationService::class);
        $verificationService->expects(self::never())->method('verifyLead');

        $subscriber = new LeadAutoVerifySubscriber($this->createEnabledConfigMock(), $verificationService);
        $subscriber->postUpdate(new PostUpdateEventArgs($lead, $this->createMock(EntityManagerInterface::class)));
        $subscriber->postFlush(new PostFlushEventArgs($this->createMock(EntityManagerInterface::class)));
    }

    private function createEnabledConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('shouldCheckOnCreate')->willReturn(true);

        return $config;
    }
}
