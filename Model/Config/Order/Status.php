<?php

namespace BlueMedia\BluePayment\Model\Config\Order;

/**
 * Order Statuses source model
 */
class Status extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string|bool
     */
    protected $_stateStatuses = false;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = $this->_stateStatuses
            ? $this->_orderConfig->getStateStatuses($this->_stateStatuses)
            : $this->_orderConfig->getStatuses();

        $options = [['value' => '', 'label' => __('-- Do not change status --')]];
        foreach ($statuses as $code => $label) {
            $options[] = ['value' => $code, 'label' => $label];
        }
        return $options;
    }
}
