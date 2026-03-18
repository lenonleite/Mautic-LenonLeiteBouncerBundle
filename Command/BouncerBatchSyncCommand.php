<?php

declare(strict_types=1);

namespace MauticPlugin\LenonLeiteBouncerBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use MauticPlugin\LenonLeiteBouncerBundle\Integration\Config;
use MauticPlugin\LenonLeiteBouncerBundle\Service\BouncerBatchService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BouncerBatchSyncCommand extends ModeratedCommand
{
    private const COMMAND_NAME = 'bouncer:verify:batch-sync';

    public function __construct(
        protected PathsHelper $pathsHelper,
        private CoreParametersHelper $coreParametersHelper,
        private Config $config,
        private BouncerBatchService $batchService,
        private TranslatorInterface $translator,
    ) {
        parent::__construct($this->pathsHelper, $this->coreParametersHelper);
    }

    public function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('lenonleitebouncer.command.batch_sync.description')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'lenonleitebouncer.command.batch_sync.option.limit');

        parent::configure();
    }

    /**
     * @param bool $new
     */
    public function execute(InputInterface $input, OutputInterface $output, $new = true): int
    {
        if (!$this->config->isEnabled()) {
            $output->writeln('<comment>'.$this->translator->trans('lenonleitebouncer.command.batch_sync.warning.disabled').'</comment>');
        }

        $limitOption = $input->getOption('limit');
        $limit       = null !== $limitOption ? max(1, (int) $limitOption) : null;
        $result      = $this->batchService->syncPendingRequests($limit);

        $output->writeln(sprintf('<info>%s: %d</info>', $this->translator->trans('lenonleitebouncer.command.batch_sync.output.requests'), $result['requests']));
        $output->writeln(sprintf('<info>%s: %d</info>', $this->translator->trans('lenonleitebouncer.command.batch_sync.output.processed'), $result['processed']));
        $output->writeln(sprintf('<info>%s: %d</info>', $this->translator->trans('lenonleitebouncer.command.batch_sync.output.updated'), $result['updated']));

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
