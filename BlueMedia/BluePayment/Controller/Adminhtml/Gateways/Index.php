<?php
/**
 * Copyright © 2016 Bold Brand Commerce
 * created by Piotr Kozioł (piotr.koziol@bold.net.pl)
 */

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 *
 * @package BlueMedia\BluePayment\Controller\Adminhtml\Gateways
 */
class Index extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\View\Result\Page
     */
    protected $_resultPage;

    /**
     * Index constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context     $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('BlueMedia_BluePayment::gateways');
    }

    /**
     * Call page factory to render layout and page content
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $this->_setPageData();

        return $this->getResultPage();
    }

    /**
     * @return $this
     */
    protected function _setPageData()
    {
        $resultPage = $this->getResultPage();
        $resultPage->setActiveMenu('BlueMedia_BluePayment::gateways');
        $resultPage->getConfig()->getTitle()->prepend((__('Gateways')));

        $resultPage->addBreadcrumb(__('BlueMedia'), __('BlueMedia'));
        $resultPage->addBreadcrumb(__('Gateways'), __('Manage Gateways'));

        return $this;
    }

    /**
     * Returns created page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function getResultPage()
    {
        if (is_null($this->_resultPage)) {
            $this->_resultPage = $this->_resultPageFactory->create();
        }

        return $this->_resultPage;
    }
}
