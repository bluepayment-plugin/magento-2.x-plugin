<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Api\Data;

/**
 * Interface GatewayInterface
 */
interface GatewayInterface
{
    public const ENTITY_ID = 'entity_id';
    public const STORE_ID = 'store_id';
    public const SERVICE_ID = 'gateway_service_id';
    public const CURRENCY = 'gateway_currency';
    public const STATUS = 'gateway_status';
    public const GATEWAY_ID = 'gateway_id';
    public const BANK_NAME = 'bank_name';
    public const NAME = 'gateway_name';
    public const DESCRIPTION = 'gateway_description';
    public const SORT_ORDER = 'gateway_sort_order';
    public const TYPE = 'gateway_type';
    public const LOGO_URL = 'gateway_logo_url';
    public const USE_OWN_LOGO = 'use_own_logo';
    public const LOGO_PATH = 'gateway_logo_path';
    public const STATUS_DATE = 'status_date';
    public const IS_SEPARATED_METHOD = 'is_separated_method';
    public const IS_FORCE_DISABLED = 'force_disable';
    public const MIN_AMOUNT = 'min_amount';
    public const MAX_AMOUNT = 'max_amount';
    public const MIN_VALIDITY_TIME = 'min_validity_time';

    /**
     * Get entity id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity id
     *
     * @param int $entityId
     * @return GatewayInterface
     */
    public function setEntityId($entityId);

    /**
     * Get Store ID for gateway.
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Set Store ID for gateway.
     *
     * @param int $store
     * @return GatewayInterface
     */
    public function setStoreId(int $store): GatewayInterface;

    /**
     * Get gateway Service ID.
     *
     * @return int
     */
    public function getServiceId(): int;

    /**
     * Set gateway Service ID.
     *
     * @param int $serviceId
     * @return GatewayInterface
     */
    public function setServiceId(int $serviceId): GatewayInterface;

    /**
     * Get gateway currency.
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Set gateway currency.
     *
     * @param string $currency
     * @return GatewayInterface
     */
    public function setCurrency(string $currency): GatewayInterface;

    /**
     * Get gateway status.
     *
     * @return boolean
     */
    public function getStatus(): bool;

    /**
     * Set gateway status.
     *
     * @param boolean $status
     * @return GatewayInterface
     */
    public function setStatus(bool $status): GatewayInterface;

    /**
     * Get gateway ID.
     *
     * @return int
     */
    public function getGatewayId(): int;

    /**
     * Set gateway ID.
     *
     * @param int $gatewayId
     * @return GatewayInterface
     */
    public function setGatewayId(int $gatewayId): GatewayInterface;

    /**
     * Get gateway bank name.
     *
     * @return string
     */
    public function getBankName(): string;

    /**
     * Set gateway bank name.
     *
     * @param string $bankName
     * @return GatewayInterface
     */
    public function setBankName(string $bankName): GatewayInterface;

    /**
     * Get gateway name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set gateway name.
     *
     * @param string $name
     * @return GatewayInterface
     */
    public function setName(string $name): GatewayInterface;

    /**
     * Get gateway description.
     *
     * @return ?string
     */
    public function getDescription(): ?string;

    /**
     * Set gateway description.
     *
     * @param ?string $description
     * @return GatewayInterface
     */
    public function setDescription(?string $description): GatewayInterface;

    /**
     * Get gateway sort order.
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set gateway sort order.
     *
     * @param int $sortOrder
     * @return GatewayInterface
     */
    public function setSortOrder(int $sortOrder): GatewayInterface;

    /**
     * Get gateway type.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get gateway type.
     *
     * @param string $type
     * @return GatewayInterface
     */
    public function setType(string $type): GatewayInterface;

    /**
     * Get gateway logo url.
     *
     * @return string
     */
    public function getLogoUrl(): string;

    /**
     * Set gateway logo url.
     *
     * @param string $logoUrl
     * @return GatewayInterface
     */
    public function setLogoUrl(string $logoUrl): GatewayInterface;

    /**
     * Get whether gateway uses custom logo.
     *
     * @return boolean
     */
    public function shouldUseOwnLogo(): bool;

    /**
     * Set whether gateway uses custom logo.
     *
     * @param boolean $useOwnLogo
     * @return GatewayInterface
     */
    public function setUseOwnLogo(bool $useOwnLogo): GatewayInterface;

    /**
     * Get gateway logo path.
     *
     * @return string
     */
    public function getLogoPath(): string;

    /**
     * Set gateway logo path.
     *
     * @param string $logoPath
     * @return GatewayInterface
     */
    public function setLogoPath(string $logoPath): GatewayInterface;

    /**
     * Returns whether gateway should be displayed as separated payment method.
     *
     * @return boolean
     */
    public function isSeparatedMethod(): bool;

    /**
     * Sets whether gateway should be displayed as separated payment method.
     *
     * @param boolean $isSeparatedMethod
     * @return GatewayInterface
     */
    public function setIsSeparatedMethod(bool $isSeparatedMethod): GatewayInterface;

    /**
     * Get gateway status date (last refresh).
     *
     * @return string
     */
    public function getStatusDate(): string;

    /**
     * Set gateway status date (last refresh).
     *
     * @param string $statusDate
     * @return GatewayInterface
     */
    public function setStatusDate(string $statusDate): GatewayInterface;

    /**
     * Is gateway force disabled.
     *
     * @return boolean
     */
    public function isForceDisabled(): bool;

    /**
     * Set should gateway be force disabled.
     *
     * @param boolean $forceDisable
     * @return GatewayInterface
     */
    public function setForceDisable(bool $forceDisable): GatewayInterface;

    /**
     * Get gateway minimal amount.
     *
     * @return ?float
     */
    public function getMinAmount(): ?float;

    /**
     * Set gateway minimal amount.
     *
     * @param ?float $minAmount
     * @return GatewayInterface
     */
    public function setMinAmount(?float $minAmount): GatewayInterface;

    /**
     * Get gateway maximal amount.
     *
     * @return ?float
     */
    public function getMaxAmount(): ?float;

    /**
     * Set gateway maximal amount.
     *
     * @param ?float $maxAmount
     * @return GatewayInterface
     */
    public function setMaxAmount(?float $maxAmount): GatewayInterface;

    /**
     * Get gateway minimal validity time.
     *
     * @return ?float
     */
    public function getMinValidityTime(): ?float;

    /**
     * Set gateway minimal validity time.
     *
     * @param ?float $minValidityTime
     * @return GatewayInterface
     */
    public function setMinValidityTime(?float $minValidityTime): GatewayInterface;
}
