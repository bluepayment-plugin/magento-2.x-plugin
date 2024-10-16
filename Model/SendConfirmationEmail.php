<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Logger\Logger;
use Exception;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class SendConfirmationEmail
{
    /** @var OrderSender */
    private $orderSender;

    /** @var Logger */
    public $logger;

    public function __construct(
        OrderSender $orderSender,
        Logger $logger
    ) {
        $this->orderSender = $orderSender;
        $this->logger = $logger;
    }

    /**
     * Check if email should be sent and try to send it.
     *
     * @param  Order  $order
     * @return bool
     */
    public function execute(Order $order): bool
    {
        $canSendNewEmail = $order->getCanSendNewEmailFlag();
        $emailSent = $order->getEmailSent();

        $this->logger->info('SendConfirmationEmail:' . __LINE__, [
            'canSendNewEmail' => $canSendNewEmail,
            'emailSent' => $emailSent,
        ]);

        if ($canSendNewEmail && !$emailSent) {
            try {
                $this->orderSender->send($order);
                return true;
            } catch (Exception $e) {
                $this->logger->critical($e);
            }
        }

        return false;
    }
}
