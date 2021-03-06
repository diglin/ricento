<?php
/**
 * ricardo.ch AG - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Ricento
 * @copyright   Copyright (c) 2015 ricardo.ch AG (http://www.ricardo.ch)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class Diglin_Ricento_Block_Adminhtml_Products_Listing_Item_Edit_Tabs
 */
class Diglin_Ricento_Block_Adminhtml_Products_Listing_Item_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('products_listing_item_tabs');
        $this->setDestElementId('edit_form');
        $this->setTemplate('widget/tabshoriz.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->addTab('selloptions_section', array(
            'label' => $this->__('Sales Options') ,
            'title' => $this->__('Sales Options') ,
            'content' => $this->getLayout()
                    ->createBlock('diglin_ricento/adminhtml_products_listing_item_edit_tabs_selloptions')
                    ->toHtml()
        ));

        $this->addTab('rules_section', array(
            'label' => $this->__('Rules') ,
            'title' => $this->__('Rules') ,
            'content' => $this->getLayout()
                    ->createBlock('diglin_ricento/adminhtml_products_listing_item_edit_tabs_rules')
                    ->toHtml()
        ));

        return parent::_beforeToHtml();
    }
}