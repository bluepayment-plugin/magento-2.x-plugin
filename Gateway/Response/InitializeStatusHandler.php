<?php

namespace BlueMedia\BluePayment\Gateway\Response;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\GetStateForStatus;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;

class InitializeStatusHandler implements HandlerInterface
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var GetStateForStatus */
    private $getStateForStatus;

    /**
     * InitializeStatusHandler constructor.
     *
     * @param  ConfigProvider       $configProvider
     * @param  GetStateForStatus    $getStateForStatus
     */
    public function __construct(
        ConfigProvider $configProvider,
        GetStateForStatus $getStateForStatus
    ) {
        $this->configProvider = $configProvider;
        $this->getStateForStatus = $getStateForStatus;
    }

    /**
     * Handle Initialize command - set correct state and status
     *
     * @param  array  $handlingSubject
     * @param  array  $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $stateObject = SubjectReader::readStateObject($handlingSubject);

        $status = $this->configProvider->getStatusWaitingPayment();
        $state = $this->getStateForStatus->execute($status, Order::STATE_PENDING_PAYMENT);

        $stateObject->setState($status);
        $stateObject->setStatus($state);
    }
}
