<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateway;

use BlueMedia\BluePayment\Helper\Gateways;
use BlueMedia\BluePayment\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;

class Synchronize extends Action
{
    /** @var ManagerInterface */
    public $messageManager;

    /** @var Gateways */
    public $gatewaysHelper;

    /** @var Logger */
    public $logger;

    /**
     * Synchronize constructor.
     *
     * @param Context $context
     * @param Gateways $gatewayHelper
     * @param Logger $logger
     */
    public function __construct(
        Context     $context,
        Gateways    $gatewayHelper,
        Logger      $logger
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->gatewaysHelper = $gatewayHelper;
        $this->logger = $logger;
    }

    /**
     * @return Redirect
     *
     * Synchronizes gateways
     *
     */
    public function execute()
    {
        $result = $this->gatewaysHelper->syncGateways();

        if (isset($result['error'])) {
            $errorMessage = $result['error'];
            $this->messageManager->addErrorMessage($errorMessage);
        } else {
            $successMessage = __('Gateway list has been synchronized!');
            $this->messageManager->addSuccessMessage($successMessage);
        }

        return $this->_redirect('*/*/index');
    }
}
