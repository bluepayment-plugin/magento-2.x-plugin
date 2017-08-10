<?php
namespace BlueMedia\BluePayment\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Gateways
 * @package BlueMedia\BluePayment\Block\Adminhtml
 */
class Gateways extends Container
{
    /**
     * constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller     = 'adminhtml_gateways';
        $this->_blockGroup     = 'BlueMedia_BluePayment';
        $this->_headerText     = __('Gateways');
        $this->_addButtonLabel = __('Synchronize Gateways');
        parent::_construct();
        $this->removeButton('add');
        $this->_addSynchronizeGatewaysButton();
    }

    /**
     * Create "Synchronize Gateways" button
     *
     * @return void
     */
    protected function _addSynchronizeGatewaysButton()
    {
        $this->addButton('synchronize_gateways', [
            'label' => __('Synchronize Gateways'),
            'onclick' => 'setLocation(\'' . $this->getUrl('*/*/synchronize') . '\')',
            'class' => 'add primary'
        ]);
    }

}