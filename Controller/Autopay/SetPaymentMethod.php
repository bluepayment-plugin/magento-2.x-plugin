<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Controller\Autopay;

use BlueMedia\BluePayment\Model\Autopay\SetPaymentMethod as SetPaymentMethodService;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class SetPaymentMethod implements HttpPostActionInterface
{
    /** @var SetPaymentMethodService */
    private $setPaymentMethod;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /**
     * SetPaymentMethod controller constructor.
     *
     * @param SetPaymentMethodService $setPaymentMethod
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        SetPaymentMethodService $setPaymentMethod,
        JsonFactory $resultJsonFactory
    ) {
        $this->setPaymentMethod = $setPaymentMethod;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Execute controller action.
     *
     * @return Json
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): Json
    {
        $this->setPaymentMethod->execute();

        return $this->resultJsonFactory->create()
            ->setData([
                'success' => true,
            ]);
    }
}
