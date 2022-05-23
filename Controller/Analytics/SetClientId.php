<?php

namespace BlueMedia\BluePayment\Controller\Analytics;

use BlueMedia\BluePayment\Model\Analytics;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;

class SetClientId implements HttpPostActionInterface
{
    /** @var RequestInterface */
    private $request;

    /** @var JsonFactory */
    private $jsonFactory;


    /** @var CartRepositoryInterface */
    private $cartRepository;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var Analytics */
    private $analytics;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        CartRepositoryInterface $cartRepository,
        StoreManagerInterface $storeManager,
        Analytics $analytics
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->cartRepository = $cartRepository;
        $this->storeManager = $storeManager;
        $this->analytics = $analytics;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $client_id = $this->request->getParam('client_id');

        $quote = $this->analytics->getQuote();
        $quote->setGaClientId($client_id);

        $this->cartRepository->save($quote);

        /** @var Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData([
            'client_id' => $client_id,
            'cart_store_id' => $quote->getStoreId(),
            'store_id' => $this->storeManager->getStore()->getId(),
        ]);
    }
}
