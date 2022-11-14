<?php

namespace BlueMedia\BluePayment\Model\Ui;

use BlueMedia\BluePayment\Gateway\Config;
use BlueMedia\BluePayment\Model\Autopay\ConfigProvider as AutopayConfigProvider;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'autopay';
    public const LOGO_SRC = 'BlueMedia_BluePayment::images/autopay_logo.png';

    /** @var AutopayConfigProvider */
    private $autopayConfig;

    /** @var AssetRepository */
    private $assetRepository;

    /**
     * @param AutopayConfigProvider $autopayConfig
     * @param AssetRepository $assetRepository
     */
    public function __construct(
        AutopayConfigProvider $autopayConfig,
        AssetRepository $assetRepository
    ) {
        $this->autopayConfig = $autopayConfig;
        $this->assetRepository = $assetRepository;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => false, // For now - don't show APC in checkout
                    'merchantId' => $this->autopayConfig->getMerchantId(),
                    'language' => $this->autopayConfig->getLanguage(),
                    'logoSrc' => $this->assetRepository->getUrl(self::LOGO_SRC),
                ],
            ],
        ];
    }
}
