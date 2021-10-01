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
    const ENTITY_ID = 'entity_id';
    const STORE_ID = 'store_id';
    const SERVICE_ID = 'gateway_service_id';
    const CURRENCY = 'gateway_currency';
    const STATUS = 'gateway_status';
    const GATEWAY_ID = 'gateway_id';
    const BANK_NAME = 'bank_name';
    const NAME = 'gateway_name';
    const DESCRIPTION = 'gateway_description';
    const SORT_ORDER = 'gateway_sort_order';
    const TYPE = 'gateway_type';
    const LOGO_URL = 'gateway_logo_url';
    const USE_OWN_LOGO = 'use_own_logo';
    const LOGO_PATH = 'gateway_logo_path';
    const STATUS_DATE = 'status_date';
    const IS_SEPARATED_METHOD = 'is_separated_method';
    const IS_FORCE_DISABLED = 'force_disable';

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
}
