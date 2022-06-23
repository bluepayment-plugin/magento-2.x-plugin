<?php

namespace BlueMedia\BluePayment\Controller\Analytics;

use BlueMedia\BluePayment\Model\Analytics;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetProductDetails implements HttpPostActionInterface
{
    /** @var RequestInterface */
    private $request;

    /** @var JsonFactory */
    private $jsonFactory;

    /** @var Analytics */
    private $analytics;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        Analytics $analytics,
        ProductRepositoryInterface $productRepository
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->analytics = $analytics;
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $productId = $this->request->getParam('product_id');
        $product = $this->productRepository->getById($productId);

        /** @var Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData([
            'id' => $product->getSku(),
            'name' => $product->getName(),
            'category' => $this->analytics->getCategoryName($product),
            'price' => $this->analytics->getPrice($product)
        ]);
    }
}
