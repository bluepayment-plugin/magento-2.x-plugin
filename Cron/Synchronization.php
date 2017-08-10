<?php
/**
 * Created by PhpStorm.
 * User: piotr
 * Date: 06.12.2016
 * Time: 15:40
 */

namespace BlueMedia\BluePayment\Cron;

use Magento\Cron\Model\Schedule;

class Synchronization {

    protected $_logger;
    protected $_gatewaysHelper;

    public function __construct(\BlueMedia\BluePayment\Helper\Gateways $gatewayHelper) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);

        $this->_gatewaysHelper = $gatewayHelper;
    }

    public function execute() {
        $this->_logger->info(__METHOD__);
        $this->_gatewaysHelper->syncGateways();
        return $this;
    }
}