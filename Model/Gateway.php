<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Model;

use BlueMedia\BluePayment\Api\Data\GatewayInterface;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway as GatewayResource;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Gateway extends AbstractModel implements IdentityInterface, GatewayInterface
{
    public const CACHE_TAG = 'blue_gateway';

    protected $_eventPrefix = 'blue_gateway';
    protected $_cacheTag = 'blue_gateway';

    /**
     * Gateway constructor.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(GatewayResource::class);
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Is gateway active (status is active and is not force disabled).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getStatus()
            && ! $this->isForceDisabled();
    }

    /**
     * Returns whether gateway should be displayed as separated payment method.
     *
     * @return bool
     */
    public function isSeparatedMethod(): bool
    {
        if (in_array($this->getGatewayId(), ConfigProvider::ALWAYS_SEPARATED)) {
            return true;
        }

        // If not PBL or FR - have to be separated
        if (! in_array($this->getType(), [ConfigProvider::TYPE_PBL, ConfigProvider::TYPE_FR])) {
            return true;
        }

        return (bool) $this->getData(self::IS_SEPARATED_METHOD);
    }

    /**
     * Get Store ID for gateway.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * Set Store ID for gateway.
     *
     * @param int $store
     * @return GatewayInterface
     */
    public function setStoreId(int $store): GatewayInterface
    {
        return $this->setData(self::STORE_ID, $store);
    }

    /**
     * Get gateway Service ID.
     *
     * @return int
     */
    public function getServiceId(): int
    {
        return (int) $this->getData(self::SERVICE_ID);
    }

    /**
     * Set gateway Service ID.
     *
     * @param int $serviceId
     * @return GatewayInterface
     */
    public function setServiceId(int $serviceId): GatewayInterface
    {
        return $this->setData(self::SERVICE_ID, $serviceId);
    }

    /**
     * Get gateway currency.
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->getData(self::CURRENCY);
    }

    /**
     * Set gateway currency.
     *
     * @param string $currency
     * @return GatewayInterface
     */
    public function setCurrency(string $currency): GatewayInterface
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * Get gateway status.
     *
     * @return bool
     */
    public function getStatus(): bool
    {
        return (bool) $this->getData(self::STATUS);
    }

    /**
     * Set gateway status.
     *
     * @param bool $status
     * @return GatewayInterface
     */
    public function setStatus(bool $status): GatewayInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get gateway ID.
     *
     * @return int
     */
    public function getGatewayId(): int
    {
        return (int) $this->getData(self::GATEWAY_ID);
    }

    /**
     * Set gateway ID.
     *
     * @param int $gatewayId
     * @return GatewayInterface
     */
    public function setGatewayId(int $gatewayId): GatewayInterface
    {
        return $this->setData(self::GATEWAY_ID, $gatewayId);
    }

    /**
     * Get gateway bank name.
     *
     * @return string
     */
    public function getBankName(): string
    {
        return $this->getData(self::BANK_NAME);
    }

    /**
     * Set gateway bank name.
     *
     * @param string $bankName
     * @return GatewayInterface
     */
    public function setBankName(string $bankName): GatewayInterface
    {
        return $this->setData(self::BANK_NAME, $bankName);
    }

    /**
     * Get gateway name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set gateway name.
     *
     * @param string $name
     * @return GatewayInterface
     */
    public function setName(string $name): GatewayInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get gateway description.
     *
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set gateway description.
     *
     * @param ?string $description
     * @return GatewayInterface
     */
    public function setDescription(?string $description): GatewayInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get gateway short description.
     *
     * @return ?string
     */
    public function getShortDescription(): ?string
    {
        return $this->getData(self::SHORT_DESCRIPTION);
    }

    /**
     * Set gateway short description.
     *
     * @param ?string $shortDescription
     * @return GatewayInterface
     */
    public function setShortDescription(?string $shortDescription): GatewayInterface
    {
        return $this->setData(self::SHORT_DESCRIPTION, $shortDescription);
    }

    /**
     * Get gateway sort order.
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    /**
     * Set gateway sort order.
     *
     * @param int $sortOrder
     * @return GatewayInterface
     */
    public function setSortOrder(int $sortOrder): GatewayInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * Get gateway type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->getData(self::TYPE);
    }

    /**
     * Get gateway type.
     *
     * @param string $type
     * @return GatewayInterface
     */
    public function setType(string $type): GatewayInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * Get gateway logo url.
     *
     * @return string
     */
    public function getLogoUrl(): string
    {
        return $this->getData(self::LOGO_URL);
    }

    /**
     * Set gateway logo url.
     *
     * @param string $logoUrl
     * @return GatewayInterface
     */
    public function setLogoUrl(string $logoUrl): GatewayInterface
    {
        return $this->setData(self::LOGO_URL, $logoUrl);
    }

    /**
     * Get whether gateway uses custom logo.
     *
     * @return bool
     */
    public function shouldUseOwnLogo(): bool
    {
        return (bool) $this->getData(self::USE_OWN_LOGO);
    }

    /**
     * Set whether gateway uses custom logo.
     *
     * @param bool $useOwnLogo
     * @return GatewayInterface
     */
    public function setUseOwnLogo(bool $useOwnLogo): GatewayInterface
    {
        return $this->setData(self::USE_OWN_LOGO, $useOwnLogo);
    }

    /**
     * Get gateway logo path.
     *
     * @return string
     */
    public function getLogoPath(): string
    {
        return $this->getData(self::LOGO_PATH);
    }

    /**
     * Set gateway logo path.
     *
     * @param string $logoPath
     * @return GatewayInterface
     */
    public function setLogoPath(string $logoPath): GatewayInterface
    {
        return $this->setData(self::LOGO_PATH, $logoPath);
    }

    /**
     * Get gateway status date (last refresh).
     *
     * @return string
     */
    public function getStatusDate(): string
    {
        return $this->getData(self::STATUS_DATE);
    }

    /**
     * Set gateway status date (last refresh).
     *
     * @param string $statusDate
     * @return GatewayInterface
     */
    public function setStatusDate(string $statusDate): GatewayInterface
    {
        return $this->setData(self::STATUS_DATE, $statusDate);
    }

    /**
     * Sets whether gateway should be displayed as separated payment method.
     *
     * @param bool $isSeparatedMethod
     * @return GatewayInterface
     */
    public function setIsSeparatedMethod(bool $isSeparatedMethod): GatewayInterface
    {
        return $this->setData(self::IS_SEPARATED_METHOD, $isSeparatedMethod);
    }

    /**
     * Is gateway force disabled.
     *
     * @return bool
     */
    public function isForceDisabled(): bool
    {
        return (bool) $this->getData(self::IS_FORCE_DISABLED);
    }

    /**
     * Set should gateway be force disabled.
     *
     * @param bool $forceDisable
     * @return GatewayInterface
     */
    public function setForceDisable(bool $forceDisable): GatewayInterface
    {
        return $this->setData(self::IS_FORCE_DISABLED, $forceDisable);
    }

    /**
     * Get gateway minimal amount.
     *
     * @return ?float
     */
    public function getMinAmount(): ?float
    {
        return $this->getData(self::MIN_AMOUNT)
            ? (float) $this->getData(self::MIN_AMOUNT)
            : null;
    }

    /**
     * Set gateway minimal amount.
     *
     * @param ?float $minAmount
     * @return GatewayInterface
     */
    public function setMinAmount(?float $minAmount): GatewayInterface
    {
        return $this->setData(self::MIN_AMOUNT, $minAmount);
    }

    /**
     * Get gateway maximal amount.
     *
     * @return ?float
     */
    public function getMaxAmount(): ?float
    {
        return $this->getData(self::MAX_AMOUNT)
            ? (float) $this->getData(self::MAX_AMOUNT)
            : null;
    }

    /**
     * Set gateway maximal amount.
     *
     * @param ?float $maxAmount
     * @return GatewayInterface
     */
    public function setMaxAmount(?float $maxAmount): GatewayInterface
    {
        return $this->setData(self::MAX_AMOUNT, $maxAmount);
    }

    /**
     * Get gateway minimal validity time.
     *
     * @return ?float
     */
    public function getMinValidityTime(): ?float
    {
        return $this->getData(self::MIN_VALIDITY_TIME)
            ? (float) $this->getData(self::MIN_VALIDITY_TIME)
            : null;
    }

    /**
     * Set gateway minimal validity time.
     *
     * @param ?float $minValidityTime
     * @return GatewayInterface
     */
    public function setMinValidityTime(?float $minValidityTime): GatewayInterface
    {
        return $this->setData(self::MIN_VALIDITY_TIME, $minValidityTime);
    }

    /**
     * Get gateway minimal validity time.
     *
     * @return ?string[]
     */
    public function getRequiredParams(): ?array
    {
        $requiredParams = $this->getData(self::REQUIRED_PARAMS);

        if (is_string($requiredParams)) {
            // change JSON to array
            $requiredParams = json_decode($requiredParams, true);
        }

        return $requiredParams;
    }

    /**
     * Set gateway minimal validity time.
     *
     * @param ?string[] $requiredParams
     * @return GatewayInterface
     */
    public function setRequiredParams(?array $requiredParams): GatewayInterface
    {
        // change array to JSON
        return $this->setData(self::REQUIRED_PARAMS, json_encode($requiredParams));
    }
}
