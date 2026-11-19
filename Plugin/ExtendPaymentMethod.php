<?php

declare(strict_types=1);

namespace BlueMedia\BluePayment\Plugin;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\Gateway;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Factory;
use Magento\Payment\Model\MethodInterface;

class ExtendPaymentMethod
{
    /** @var Factory */
    private $factory;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var Gateway[] */
    private $gateways = [];

    /** @var bool[] separated codes without an active gateway, keyed by code */
    private $missingCodes = [];

    /**
     * @param  Factory $factory
     * @param  ConfigProvider $configProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Factory $factory,
        ConfigProvider $configProvider
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;
    }

    /**
     * Modify results of getPaymentMethods() call to add separated methods
     *
     * @param Data $subject
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function afterGetPaymentMethods(Data $subject, array $result): array
    {
        if ($this->configProvider->isGatewaySelectionEnabled()) {
            $config = $this->configProvider->getPaymentConfig();

            foreach ($this->configProvider->getSeparatedGateways() as $gateway) {
                $code = Payment::SEPARATED_PREFIX_CODE.$gateway->getGatewayId();
                $result[$code] = $result['bluepayment'];
                $result[$code]['title'] = $gateway->getName();

                $this->gateways[$code] = $gateway;
            }

            if (isset($result['bluepayment'])) {
                // Remove if any gateway is not available
                $options = $config['options'];
                $separated = $config['separated'];
                if ($options !== false && count($options) === 0 && count($separated) === 0) {
                    unset($result['bluepayment']);
                }
            }
        }

        return $result;
    }

    /**
     * Modify results of getMethodInstance() call to add in details about Autppay payment methods
     *
     * @param Data $subject
     * @param callable $proceed
     * @param string $code
     * @return MethodInterface
     * @throws LocalizedException
     * @throws \UnexpectedValueException when no active gateway matches the code
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function aroundGetMethodInstance(Data $subject, callable $proceed, string $code): MethodInterface
    {
        if (strpos($code, Payment::SEPARATED_PREFIX_CODE) !== 0) {
            return $proceed($code);
        }

        if (!isset($this->gateways[$code]) && !isset($this->missingCodes[$code])) {
            foreach ($this->configProvider->getSeparatedGateways() as $gateway) {
                $this->gateways[Payment::SEPARATED_PREFIX_CODE.$gateway['gateway_id']] = $gateway;
            }
        }

        if (!isset($this->gateways[$code])) {
            $this->missingCodes[$code] = true;
            // Payment\Model\Info::getMethodInstance() falls back to Method\Substitution on this exception type
            throw new \UnexpectedValueException(
                sprintf('No active gateway for payment method "%s".', $code)
            );
        }

        /** @var Payment $payment */
        $payment = $this->factory->create(Payment::class);
        $payment->setCode($code);
        $payment->setGatewayModel($this->gateways[$code]);

        return $payment;
    }

    /**
     * Forget resolved and unresolved gateways so a long-running process starts each request with empty maps.
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->gateways = [];
        $this->missingCodes = [];
    }
}
