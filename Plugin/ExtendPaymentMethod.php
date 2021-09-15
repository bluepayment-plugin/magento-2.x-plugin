<?php

namespace BlueMedia\BluePayment\Plugin;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\Payment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Factory;
use Magento\Payment\Model\MethodInterface;

class ExtendPaymentMethod
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    private $names = [];

    /**
     * @param Factory $factory
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
     * @param                              $result
     * @return array
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function afterGetPaymentMethods(Data $subject, $result)
    {
        if ($this->configProvider->getPaymentConfig()['bluePaymentOptions'] != false) {
            foreach ($this->configProvider->getPaymentConfig()['bluePaymentSeparated'] as $separated) {
                $code = 'bluepayment_'.$separated['gateway_id'];
                $result[$code] = $result['bluepayment'];
                $result[$code]['title'] = $separated['name'];

                $this->names[$code] = $separated['name'];
            }

            if (isset($result['bluepayment'])) {
                // Remove if any gateway is not available
                $options = $this->configProvider->getPaymentConfig()['bluePaymentOptions'];
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
     * @param callable                     $proceed
     * @param string                       $code
     * @return MethodInterface
     * @throws LocalizedException
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function aroundGetMethodInstance(Data $subject, callable $proceed, $code)
    {
        if (false === strpos($code, 'bluepayment_')) {
            return $proceed($code);
        }

        $payment = $this->factory->create(Payment::class);
        $payment->setCode($code);

        if (! $this->names) {
            foreach ($this->configProvider->getPaymentConfig()['bluePaymentSeparated'] as $separated) {
                $this->names['bluepayment_'.$separated['gateway_id']] = $separated['name'];
            }
        }

        $payment->setTitle($this->names[$code]);

        return $payment;
    }
}
