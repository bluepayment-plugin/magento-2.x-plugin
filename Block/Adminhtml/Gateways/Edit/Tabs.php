<?php

namespace BlueMedia\BluePayment\Block\Adminhtml\Gateways\Edit;

use BlueMedia\BluePayment\Block\Adminhtml\Gateways\Edit\Tab\Info as InfoTab;
use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

/**
 * Edit gateway tabs
 */
class Tabs extends WidgetTabs
{
    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('gateways_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Ustawienia'));
    }

    /**
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->addTab('gateways_info', [
            'label'   => __('General'),
            'title'   => __('General'),
            'content' => $this->getLayout()->createBlock(InfoTab::class)->toHtml(),
            'active'  => true,
        ]);

        return parent::_beforeToHtml();
    }
}
