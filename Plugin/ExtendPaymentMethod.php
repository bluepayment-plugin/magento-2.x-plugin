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
                $options = $config['bluePaymentOptions'];
                if ($options !== false && count($options) === 0) {
                    unset($result['bluepayment']);
                }
            }
        }

        return $result;
    }

    /**
     * Modify results of getMethodInstance() call to add in details about BM payment methods
     *
     * @param Data $subject
     * @param callable $proceed
     * @param string $code
     * @return MethodInterface
     * @throws LocalizedException
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function aroundGetMethodInstance(Data $subject, callable $proceed, string $code): MethodInterface
    {
        if (false === strpos($code, Payment::SEPARATED_PREFIX_CODE)) {
            return $proceed($code);
        }

        /** @var Payment $payment */
        $payment = $this->factory->create(Payment::class);
        $payment->setCode($code);

        if (! $this->gateways) {
            foreach ($this->configProvider->getSeparatedGateways() as $gateway) {
                $this->gateways[Payment::SEPARATED_PREFIX_CODE.$gateway['gateway_id']] = $gateway;
            }
        }

        $model = $this->gateways[$code];
        $payment->setGatewayModel($model);

        return $payment;
    }
}
