<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Autopay;

use BlueMedia\BluePayment\Api\ShouldShowAutopayInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Check if Autopay should be shown in catalog/cart page.
 */
class ShouldShowAutopay implements ShouldShowAutopayInterface
{
    /**
     * @var ConfigProvider
     */
    private $config;

    /** @var RequestInterface */
    private $request;

    /** @var string $currencyCode */
    private $requestKey;

    /**
     * ShouldShowAutopay constructor.
     *
     * @param ConfigProvider $config
     * @param RequestInterface $request
     * @param string $requestKey
     */
    public function __construct(
        ConfigProvider   $config,
        RequestInterface $request,
        string           $requestKey
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->requestKey = $requestKey;
    }

    /**
     * Check if Autopay should be shown in catalog/cart page.
     *
     * @return bool
     */
    public function execute(): bool
    {
        if ($this->config->isActive()) {
            return true;
        }

        if ($this->config->isHidden()) {
            return $this->checkRequest();
        }

        return false;
    }

    /**
     * Check if in
     *
     * @return bool
     */
    private function checkRequest(): bool
    {
        $params = $this->request->getParams();

        return isset($params[$this->requestKey]);
    }
}
