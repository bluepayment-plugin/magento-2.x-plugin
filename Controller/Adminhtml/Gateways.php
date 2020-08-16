<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml;

use BlueMedia\BluePayment\Model\GatewaysFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use BlueMedia\BluePayment\Logger\Logger;

/**
 * Class Gateways
 *
 * @package BlueMedia\BluePayment\Controller\Adminhtml
 */
abstract class Gateways extends Action
{
    /** @var Registry */
    public $coreRegistry;

    /** @var PageFactory */
    public $resultPageFactory;

    /** @var GatewaysFactory */
    public $gatewaysFactory;

    /** @var Logger */
    public $logger;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param GatewaysFactory $gatewaysFactory
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        GatewaysFactory $gatewaysFactory,
        Logger $logger
    )
    {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->gatewaysFactory = $gatewaysFactory;
        $this->logger = $logger;
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
