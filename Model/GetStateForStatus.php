<?php

namespace BlueMedia\BluePayment\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;

class GetStateForStatus
{
    /** @var Collection */
    private $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function execute($status, $default = Order::STATE_NEW)
    {
        if (!empty($status)) {
            foreach ($this->collection->joinStates() as $item) {
                /** @var Status $item */
                if ($item->getStatus() == $status) {
                    return $item->getState();
                }
            }
        }

        return $default;
    }
}
