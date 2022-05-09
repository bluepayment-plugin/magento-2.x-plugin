<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml;

use BlueMedia\BluePayment\Api\Data\GatewayInterfaceFactory;
use BlueMedia\BluePayment\Api\GatewayRepositoryInterface;
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
abstract class Gateway extends Action
{
    /** @var Registry */
    public $coreRegistry;

    /** @var PageFactory */
    public $resultPageFactory;

    /** @var GatewayInterfaceFactory */
    public $gatewayFactory;

    /** @var GatewayRepositoryInterface */
    public $gatewayRepository;

    /** @var Logger */
    public $logger;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param GatewayInterfaceFactory $gatewayFactory
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        GatewayInterfaceFactory $gatewayFactory,
        GatewayRepositoryInterface $gatewayRepository,
        Logger $logger
    )
    {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->gatewayFactory = $gatewayFactory;
        $this->gatewayRepository = $gatewayRepository;
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

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function initPage($resultPage)
    {
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE)
            ->addBreadcrumb(__('BlueMedia'), __('BlueMedia'))
            ->addBreadcrumb(__('Gateway'), __('Gateway'));
        return $resultPage;
    }
}
