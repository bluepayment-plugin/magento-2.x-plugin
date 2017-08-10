<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use BlueMedia\BluePayment\Model\GatewaysFactory;

/**
 * Class Gateways
 * @package BlueMedia\BluePayment\Controller\Adminhtml
 */
abstract class Gateways extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Result page factory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * Gateways model factory
     *
     * @var \BlueMedia\BluePayment\Model\GatewaysFactory
     */
    protected $_gatewaysFactory;

    /**
     * Used for creating logs
     *
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * @param Context         $context
     * @param Registry        $coreRegistry
     * @param PageFactory     $resultPageFactory
     * @param GatewaysFactory $gatewaysFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        GatewaysFactory $gatewaysFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry      = $coreRegistry;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_gatewaysFactory   = $gatewaysFactory;

        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
    }

    /**
     * GatewaysFactory access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('BlueMedia_BluePayment::gateways');
    }
}