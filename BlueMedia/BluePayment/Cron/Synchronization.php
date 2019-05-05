<?php
/**
 * Created by PhpStorm.
 * User: piotr
 * Date: 06.12.2016
 * Time: 15:40
 */

namespace BlueMedia\BluePayment\Cron;

/**
 * Class Synchronization
 *
 * @package BlueMedia\BluePayment\Cron
 */
class Synchronization
{
    /**
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * @var \BlueMedia\BluePayment\Helper\Gateways
     */
    protected $_gatewaysHelper;

    /**
     * Synchronization constructor.
     *
     * @param \BlueMedia\BluePayment\Helper\Gateways $gatewayHelper
     */
    public function __construct(\BlueMedia\BluePayment\Helper\Gateways $gatewayHelper)
    {
        $writer        = new \Zend\Log\Writer\Stream(BP . '/var/log/bluemedia.log');
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);

        $this->_gatewaysHelper = $gatewayHelper;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $this->_logger->info(__METHOD__);
        $this->_gatewaysHelper->syncGateways();

        return $this;
    }
}
