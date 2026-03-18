<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Tests\Unit\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use MauticPlugin\LenonLeiteBouncerBundle\Command\BouncerBatchCreateCommand;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerBatchService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Translation\TranslatorInterface;

class BouncerBatchCreateCommandTest extends TestCase
{
    public function testItFailsWhenPluginIsDisabled(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(false);

        $commandTester = new CommandTester(new BouncerBatchCreateCommand(
            $this->createMock(PathsHelper::class),
            $this->createMock(CoreParametersHelper::class),
            $config,
            $this->createMock(BouncerBatchService::class),
            $this->createTranslator(),
        ));

        $exitCode = $commandTester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('not enabled', $commandTester->getDisplay());
    }

    public function testItCreatesBatchWhenEnabled(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->willReturn(true);

        $service = $this->createMock(BouncerBatchService::class);
        $service->expects(self::once())
            ->method('createBatch')
            ->with(25, 10)
            ->willReturn([
                'submitted'  => 25,
                'batch_id'   => 'batch-123',
                'request_id' => 5,
            ]);

        $commandTester = new CommandTester(new BouncerBatchCreateCommand(
            $this->createMock(PathsHelper::class),
            $this->createMock(CoreParametersHelper::class),
            $config,
            $service,
            $this->createTranslator(),
        ));

        $exitCode = $commandTester->execute([
            '--limit'  => 25,
            '--min-id' => 10,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Submitted: 25', $commandTester->getDisplay());
        self::assertStringContainsString('Batch ID: batch-123', $commandTester->getDisplay());
    }

    private function createTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(static function (string $id): string {
                return match ($id) {
                    'lenonleitebouncer.command.batch_create.error.disabled' => 'Bouncer plugin is not enabled.',
                    'lenonleitebouncer.command.batch_create.output.submitted' => 'Submitted',
                    'lenonleitebouncer.command.batch_create.output.batch_id' => 'Batch ID',
                    'lenonleitebouncer.command.batch_create.output.request_id' => 'Request ID',
                    default => $id,
                };
            });

        return $translator;
    }
}
