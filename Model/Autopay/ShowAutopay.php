<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Autopay;

use BlueMedia\BluePayment\Api\ShouldShowAutopayInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Check if Autopay should be shown in catalog/cart page.
 */
class ShowAutopay implements ShouldShowAutopayInterface
{
    public const COOKIE_VALUE = 'enabled';

    /**
     * @var ConfigProvider
     */
    private $config;

    /** @var RequestInterface */
    private $request;

    /** @var CookieManagerInterface */
    private $cookieManager;

    /** @var CookieMetadataFactory */
    private $cookieMetadataFactory;

    /** @var string */
    private $requestKey;

    /**
     * ShouldShowAutopay constructor.
     *
     * @param ConfigProvider $config
     * @param RequestInterface $request
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param string $requestKey
     */
    public function __construct(
        ConfigProvider $config,
        RequestInterface $request,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        string $requestKey
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->requestKey = $requestKey;
    }

    /**
     * Check if Autopay should be shown in catalog/cart page.
     *
     * @return bool
     */
    public function shouldShow(): bool
    {
        if ($this->config->isActive()) {
            return true;
        }

        if ($this->config->isHidden() && $this->checkRequest()) {
            return true;
        }

        return false;
    }

    /**
     * Check if autopay should be enabled - by request and/or cookie.
     *
     * If autopay is enabled by request, add cookie to response.
     *
     * @return bool
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @throws InputException
     */
    public function checkRequest(): bool
    {
        if ($this->checkRequestParam() || $this->checkCookie()) {
            $this->addCookie();
            return true;
        }

        return false;
    }

    /**
     * Check if autopay is enabled by request param.
     *
     * @return bool
     */
    private function checkRequestParam(): bool
    {
        $params = $this->request->getParams();
        return isset($params[$this->requestKey]);
    }

    /**
     * Check if autopay is enabled by cookie.
     *
     * @return bool
     */
    private function checkCookie(): bool
    {
        $cookie = $this->cookieManager->getCookie($this->requestKey);
        return $cookie === self::COOKIE_VALUE;
    }

    /**
     * Add cookie to response.
     *
     * @return void
     * @throws InputException
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     */
    private function addCookie()
    {
        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDurationOneYear();

        $this->cookieManager->setPublicCookie(
            $this->requestKey,
            self::COOKIE_VALUE,
            $metadata
        );
    }
}
