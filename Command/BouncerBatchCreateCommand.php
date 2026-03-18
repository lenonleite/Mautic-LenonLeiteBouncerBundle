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

class BouncerBatchCreateCommand extends ModeratedCommand
{
    private const COMMAND_NAME = 'bouncer:verify:batch-create';

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
            ->setDescription('lenonleitebouncer.command.batch_create.description')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'lenonleitebouncer.command.batch_create.option.limit', 100)
            ->addOption('min-id', 'm', InputOption::VALUE_OPTIONAL, 'lenonleitebouncer.command.batch_create.option.min_id', 0);

        parent::configure();
    }

    /**
     * @param bool $new
     */
    public function execute(InputInterface $input, OutputInterface $output, $new = true): int
    {
        if (!$this->config->isEnabled()) {
            $output->writeln('<error>'.$this->translator->trans('lenonleitebouncer.command.batch_create.error.disabled').'</error>');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $limit = max(1, (int) $input->getOption('limit'));
        $minId = max(0, (int) $input->getOption('min-id'));

        $result = $this->batchService->createBatch($limit, $minId);

        $output->writeln(sprintf('<info>%s: %d</info>', $this->translator->trans('lenonleitebouncer.command.batch_create.output.submitted'), $result['submitted']));
        $output->writeln(sprintf('<info>%s: %s</info>', $this->translator->trans('lenonleitebouncer.command.batch_create.output.batch_id'), $result['batch_id']));
        $output->writeln(sprintf('<info>%s: %s</info>', $this->translator->trans('lenonleitebouncer.command.batch_create.output.request_id'), (string) ($result['request_id'] ?? '')));

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
