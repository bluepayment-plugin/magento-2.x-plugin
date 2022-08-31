<?php

namespace BlueMedia\BluePayment\Controller\Processing;

use BlueMedia\BluePayment\Helper\Webapi;
use BlueMedia\BluePayment\Logger\Logger;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface as LocaleResolverInterface;

/**
 * Class Create
 */
class Agreements implements HttpGetActionInterface
{
    const PARAM_GATEWAY_ID = 'gateway_id';

    /** @var RequestInterface */
    private $request;

    /** @var Webapi */
    private $webapi;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var LocaleResolverInterface */
    private $localeResolver;

    /** @var Logger */
    private $logger;

    /**
     * @param Webapi $webapi
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        RequestInterface $request,
        Webapi $webapi,
        JsonFactory $resultJsonFactory,
        CheckoutSession $checkoutSession,
        LocaleResolverInterface $localeResolver,
        Logger $logger
    ) {
        $this->request = $request;
        $this->webapi = $webapi;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->localeResolver = $localeResolver;
        $this->logger = $logger;
    }

    /**
     * Pobranie zgód dla danej metody płatności
     *
     * @return Json
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        /** @var string|null $gatewayId */
        $gatewayId = $this->request->getParam(self::PARAM_GATEWAY_ID);
        $currency = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();
        $locale = $this->localeResolver->getLocale();

        if ($currency !== 'PLN' || $gatewayId === null) {
            // Currently, not supported
            $resultJson->setData([]);

            return $resultJson;
        }

        $response = $this->webapi->agreements(
            (int) $gatewayId,
            $currency,
            $locale
        );

        if (is_array($response) && $response['result'] == 'OK') {
            $resultJson->setData($response['regulationList']);
        } else {
            $this->logger->addError('Unable to get agreements.', [
                'gatewayId' => $gatewayId,
                'currency' => $currency,
                'locale' => $locale,
                'response' => $response
            ]);

            $resultJson->setData([
                'error' => 'Nie można pobrać zgód dla danej metody płatniczej.',
            ]);
        }

        return $resultJson;
    }
}
