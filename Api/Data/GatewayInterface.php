<?php
/**
 * Created by PhpStorm.
 * User: piotr
 * Date: 29.11.2016
 * Time: 12:06
 */

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
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     * @return GatewayInterface
     */
    public function setEntityId($entityId);

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param int $store
     * @return GatewayInterface
     */
    public function setStoreId($store);

    /**
     * @return int
     */
    public function getServiceId();

    /**
     * @param int $serviceId
     * @return GatewayInterface
     */
    public function setServiceId($serviceId);

    /**
     * @return string
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return GatewayInterface
     */
    public function setCurrency($currency);

    /**
     * @return boolean
     */
    public function getStatus();

    /**
     * @param boolean $status
     * @return GatewayInterface
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getGatewayId();

    /**
     * @param int $gatewayId
     * @return GatewayInterface
     */
    public function setGatewayId($gatewayId);

    /**
     * @return string
     */
    public function getBankName();

    /**
     * @param string $bankName
     * @return GatewayInterface
     */
    public function setBankName($bankName);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     * @return GatewayInterface
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $description
     * @return GatewayInterface
     */
    public function setDescription($description);

    /**
     * @return int
     */
    public function getSortOrder();

    /**
     * @param int $sortOrder
     * @return GatewayInterface
     */
    public function setSortOrder($sortOrder);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $type
     * @return GatewayInterface
     */
    public function setType($type);

    /**
     * @return string
     */
    public function getLogoUrl();
    /**
     * @param string $logoUrl
     * @return GatewayInterface
     */
    public function setLogoUrl($logoUrl);

    /**
     * @return boolean
     */
    public function getUseOwnLogo();

    /**
     * @param boolean $useOwnLogo
     * @return GatewayInterface
     */
    public function setUseOwnLogo($useOwnLogo);

    /**
     * @return string
     */
    public function getLogoPath();

    /**
     * @param string $logoPath
     * @return GatewayInterface
     */
    public function setLogoPath($logoPath);

    /**
     * @return boolean
     */
    public function getIsSeparatedMethod();

    /**
     * @param boolean $isSeparatedMethod
     * @return GatewayInterface
     */
    public function setIsSeparatedMethod($isSeparatedMethod);

    /**
     * @return boolean
     */
    public function getStatusDate();

    /**
     * @param string $statusDate
     * @return GatewayInterface
     */
    public function setStatusDate($statusDate);

    /**
     * @return boolean
     */
    public function isForceDisabled();

    /**
     * @param boolean $forceDisable
     * @return GatewayInterface
     */
    public function setForceDisable($forceDisable);

    /**
     * @return ?float
     */
    public function getMinAmount();

    /**
     * @param ?float $minAmount
     * @return GatewayInterface
     */
    public function setMinAmount($minAmount);

    /**
     * @return ?float
     */
    public function getMaxAmount();

    /**
     * @param ?float $maxAmount
     * @return GatewayInterface
     */
    public function setMaxAmount($maxAmount);

    /**
     * @return ?float
     */
    public function getMinValidityTime();

    /**
     * @param ?float $minValidityTime
     * @return GatewayInterface
     */
    public function setMinValidityTime($minValidityTime);
}
