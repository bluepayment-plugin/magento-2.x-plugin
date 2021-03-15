<?php

namespace BlueMedia\BluePayment\Payment\Helper;

class DataPlugin
{

    /**
     * Modify results of getPaymentMethods() call to add in Klarna methods returned by API
     *
     * @param \Magento\Payment\Helper\Data $subject
     * @param                              $result
     * @return array
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function afterGetPaymentMethods(\Magento\Payment\Helper\Data $subject, $result)
    {
        $code = 'bluepayment_gpay';
        $result[$code] = $result['bluepayment'];
        $result[$code]['title'] = 'Google Pay';

        return $result;
    }


    /**
     * Modify results of getMethodInstance() call to add in details about Klarna payment methods
     *
     * @param \Magento\Payment\Helper\Data $subject
     * @param callable                     $proceed
     * @param string                       $code
     * @return MethodInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function aroundGetMethodInstance(\Magento\Payment\Helper\Data $subject, callable $proceed, $code)
    {
        if (false === strpos($code, 'bluepayment_')) {
            return $proceed($code);
        }

        return $this->paymentMethodList->getPaymentMethod($code);
    }
}
