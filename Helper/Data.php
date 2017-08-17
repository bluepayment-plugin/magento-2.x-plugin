<?php


namespace BlueMedia\BluePayment\Helper;

/**
 * Class Data
 *
 * @package BlueMedia\BluePayment\Helper
 */
class Data extends \Magento\Payment\Helper\Data
{
    /**
     * @param array $data
     *
     * @return string
     */
    public function generateAndReturnHash($data)
    {
        $algorithm           = $this->scopeConfig->getValue("payment/bluepayment/hash_algorithm");
        $separator           = $this->scopeConfig->getValue("payment/bluepayment/hash_separator");
        $values_array        = array_values($data);
        $values_array_filter = array_filter(($values_array));
        $comma_separated     = implode(",", $values_array_filter);
        $replaced            = str_replace(",", $separator, $comma_separated);
        $hash                = hash($algorithm, $replaced);

        return $hash;
    }
}
