<?php
/**
 * Copyright © 2016 Bold Brand Commerce
 * created by Piotr Kozioł (piotr.koziol@bold.net.pl)
 */
namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use BlueMedia\BluePayment\Helper\Gateways;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Synchronize
 * @package BlueMedia\BluePayment\Controller\Adminhtml\Gateways
 */
class Synchronize extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \BlueMedia\BluePayment\Helper\Gateways
     */
    protected $_gatewaysHelper;

    /**
     * @var \Zend\Log\Logger
     */
    protected $_logger;

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
        $this->_resultPageFactory = $resultPageFactory;

        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);

        $this->_messageManager = $context->getMessageManager();
        $this->_gatewaysHelper = $gatewaysHelper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     *
     * Synchronizes gateways
     *
     */
    public function execute()
    {
        $result = $this->_gatewaysHelper->syncGateways();

        if (isset($result['error'])) {
            $errorMessage = $result['error'];
            $this->_messageManager->addError($errorMessage);
        } else {
            $successMessage = __('Gateway list has been synchronized!');
            $this->_messageManager->addSuccess($successMessage);
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminbluepayment/gateways/index');

        return $resultRedirect;
    }

}