<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Console\Command;

use BlueMedia\BluePayment\Api\RefundStatusUpdaterInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRefundStatusCommand extends Command
{
    /**
     * @var RefundStatusUpdaterInterface
     */
    protected $refundStatusUpdater;

    /**
     * @var State
     */
    protected $state;

    public function __construct(
        RefundStatusUpdaterInterface $refundStatusUpdater,
        State $state
    ) {
        $this->refundStatusUpdater = $refundStatusUpdater;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('bluepayment:refund:update-status');
        $this->setDescription('Refresh refund statuses from the external Autopay API.');
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     *
     * @return void
     * @throws LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $this->refundStatusUpdater->updateRefundStatuses();

        $output->writeln('Refund statuses updated.');

        return 0;
    }
}
