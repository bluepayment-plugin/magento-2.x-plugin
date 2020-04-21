<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Helper\Gateways;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class Synchronize extends Action
{
    /** @var PageFactory */
    public $resultPageFactory;

    /** @var ManagerInterface */
    public $messageManager;

    /** @var Gateways */
    public $gatewaysHelper;

    /** @var Logger */
    public $logger;

    /**
     * Synchronize constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \BlueMedia\BluePayment\Helper\Gateways     $gatewaysHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Gateways $gatewaysHelper
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;

        $writer = new Stream(BP . '/var/log/bluemedia.log');
        $this->logger = new Logger();
        $this->logger->addWriter($writer);

        $this->messageManager = $context->getMessageManager();
        $this->gatewaysHelper = $gatewaysHelper;
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
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminbluepayment/gateways/index');

        return $resultRedirect;
    }
}
