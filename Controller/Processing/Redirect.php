<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Redirect to payment controller
 */
class Redirect extends Action
{
    /**
     * @var PageFactory
     */
    public $pageFactory;

    /** @var Session */
    public $session;

    public function __construct(Context $context, PageFactory $pageFactory, Session $session)
    {
        parent::__construct($context);

        $this->pageFactory = $pageFactory;
        $this->session = $session;
    }

    /**
     * Przekierowanie do płatności
     *
     * @return ResponseInterface|Json
     */
    public function execute()
    {
        $page = $this->pageFactory->create();

        /** @var \BlueMedia\BluePayment\Block\Processing\Redirect $block */
        $block = $page->getLayout()->getBlock('bluepayment.processing.redirect');

        $this->session->start();
        $redirectUrl = $this->session->getRedirectUrl();
        $waitingPageSeconds = $this->session->getWaitingPageSeconds();

        $block->addData([
            'redirectURL' => $redirectUrl,
            'seconds' => $waitingPageSeconds
        ]);

        return $page;
    }
}
