<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use MauticPlugin\LenonLeiteBouncerBundle\EventListener\LeadSubscriber;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerVerificationService;
use PHPUnit\Framework\TestCase;

class LeadSubscriberTest extends TestCase
{
    public function testVerifiesBrandNewLeadWithEmail(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getEmail')->willReturn('alice@example.com');

        $event = $this->createMock(LeadEvent::class);
        $event->method('isNew')->willReturn(true);
        $event->method('getLead')->willReturn($lead);

        $verificationService = $this->createMock(BouncerVerificationService::class);
        $verificationService->expects(self::once())
            ->method('verifyLead')
            ->with($lead);

        $subscriber = new LeadSubscriber($this->createEnabledConfigMock(), $verificationService);
        $subscriber->onLeadPostSave($event);
    }

    public function testVerifiesExistingLeadWhenEmailWasAdded(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getEmail')->willReturn('alice@example.com');
        $lead->method('getChanges')->willReturn([
            'fields' => [
                'email' => ['', 'alice@example.com'],
            ],
        ]);

        $event = $this->createMock(LeadEvent::class);
        $event->method('isNew')->willReturn(false);
        $event->method('getLead')->willReturn($lead);

        $verificationService = $this->createMock(BouncerVerificationService::class);
        $verificationService->expects(self::once())
            ->method('verifyLead')
            ->with($lead);

        $subscriber = new LeadSubscriber($this->createEnabledConfigMock(), $verificationService);
        $subscriber->onLeadPostSave($event);
    }

    public function testDoesNotVerifyExistingLeadWhenEmailDidNotChange(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->method('getEmail')->willReturn('alice@example.com');
        $lead->method('getChanges')->willReturn([]);

        $event = $this->createMock(LeadEvent::class);
        $event->method('isNew')->willReturn(false);
        $event->method('getLead')->willReturn($lead);

        $verificationService = $this->createMock(BouncerVerificationService::class);
        $verificationService->expects(self::never())->method('verifyLead');

        $subscriber = new LeadSubscriber($this->createEnabledConfigMock(), $verificationService);
        $subscriber->onLeadPostSave($event);
    }

    private function createEnabledConfigMock(): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $config->method('shouldCheckOnCreate')->willReturn(true);

        return $config;
    }
}
