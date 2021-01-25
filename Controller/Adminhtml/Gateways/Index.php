<?php

namespace BlueMedia\BluePayment\Controller\Adminhtml\Gateways;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
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
        $this->resultPageFactory = $resultPageFactory;

        parent::__construct($context);
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
     * @return Page
     */
    public function execute()
    {
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('BlueMedia_BluePayment::gateways');
        $page->getConfig()->getTitle()->prepend((__('Gateways')));

        return $page;
    }
}
