<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 *
 * @package BlueMedia\BluePayment\Controller\Adminhtml\Gateways
 */
class Index extends Action
{
    /** @var PageFactory */
    public $resultPageFactory;

    /** @var Page */
    public $resultPage;

    /**
     * Index constructor.
     *
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
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
    public function _setPageData()
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
     * @return Page
     */
    public function getResultPage()
    {
        if ($this->resultPage === null) {
            $this->resultPage = $this->resultPageFactory->create();
        }

        return $this->resultPage;
    }
}
