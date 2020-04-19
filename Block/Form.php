<?php

namespace BlueMedia\BluePayment\Block;

use BlueMedia\BluePayment\Model\ResourceModel\Gateways\Collection;
use BlueMedia\BluePayment\Model\ResourceModel\Gateways\CollectionFactory;
use Magento\Framework\View\Element\Template\Context;

class Form extends \Magento\Payment\Block\Form
{
    /** @var string */
    protected $_template = 'BlueMedia_BluePayment::bluepayment/form.phtml';

    /** @var array|Collection */
    private $gatewayList = [];

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * Form constructor.
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return array|Collection
     */
    public function getGatewaysList()
    {
        if (!$this->gatewayList) {
            $this->gatewayList = $this->collectionFactory->create();
            $this->gatewayList
                ->addFieldToFilter('gateway_status', (string)1)
                ->addFieldToFilter('force_disable', (string)0)
                ->load();
        }

        return $this->gatewayList;
    }

    /**
     * Zwraca adres do logo firmy
     *
     * @return string|bool
     */
    public function getLogoSrc()
    {
        $logo_src = $this->getViewFileUrl('BlueMedia_BluePayment::images/logo.jpg');

        return $logo_src != '' ? $logo_src : false;
    }
}
