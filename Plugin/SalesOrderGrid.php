<?php

namespace BlueMedia\BluePayment\Plugin;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

class SalesOrderGrid
{
    public static $table = 'sales_order_grid';

    /**
     * @param Collection $subject
     * @return null
     */
    public function beforeLoad(Collection $subject)
    {
        if (!$subject->isLoaded()) {
            $paymentTable = $subject->getResource()->getTable('sales_order_payment');
            $gatewayTable = $subject->getResource()->getTable('blue_gateways');

            $subject->getSelect()
                ->joinLeft(
                    $paymentTable,
                    $paymentTable . '.parent_id = main_table.entity_id',
                    [
                        'JSON_EXTRACT(' . $paymentTable . '.additional_information, "$.bluepayment_gateway") as bluepayment_gateway_id'
                    ]
                )
                ->joinLeft(
                    $gatewayTable,
                    $gatewayTable.'.gateway_id = JSON_EXTRACT(' . $paymentTable . '.additional_information, "$.bluepayment_gateway") AND '.$gatewayTable.'.gateway_currency = main_table.order_currency_code',
                    'gateway_name as payment_channel'
                );
        }

        return null;
    }
}
