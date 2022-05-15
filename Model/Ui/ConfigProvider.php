<?php

namespace BlueMedia\Autopay\Model\Ui;

use BlueMedia\Autopay\Gateway\Config;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'autopay';
    public const LOGO_SRC = 'BlueMedia_Autopay::images/autopay_logo.png';

    /** @var Config */
    private $config;

    /** @var AssetRepository */
    private $assetRepository;

    /**
     * @param  Config  $config
     * @param  AssetRepository  $assetRepository
     */
    public function __construct(
        Config $config,
        AssetRepository $assetRepository
    ) {
        $this->config = $config;
        $this->assetRepository = $assetRepository;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'logoSrc' => $this->assetRepository->getUrl(self::LOGO_SRC),
                ],
            ],
        ];
    }
}
