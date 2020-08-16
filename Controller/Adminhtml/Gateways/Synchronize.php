<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Helper\Gateways;
use BlueMedia\BluePayment\Logger\Logger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;

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
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Gateways $gatewaysHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Gateways $gatewaysHelper,
        Logger $logger
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->messageManager = $context->getMessageManager();
        $this->gatewaysHelper = $gatewaysHelper;
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
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminbluepayment/gateways/index');

        return $resultRedirect;
    }
}
