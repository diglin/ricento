<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Ricento
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Collection of Products_Listing_Item
 */
class Diglin_Ricento_Model_Resource_Products_Listing_Item_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @var bool
     */
    protected $_isProductTableJoined = false;

    /**
     * @var bool
     */
    protected $_isProductConfigurableTableJoined = false;

    /**
     * Products_Listing_Item Collection Resource Constructor
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('diglin_ricento/products_listing_item');
    }

    /**
     * @param $status
     * @return $this
     */
    public function updateStatusToAll($status)
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getTable('diglin_ricento/products_listing_item'),
                array('status' => $status),
                array('item_id IN (?)' => $this->getAllIds())
        );

        return $this;
    }

    /**
     * Get only items collection being a configurable product
     *
     * @return $this
     */
    public function getConfigurableProducts()
    {
        if (!$this->_isProductConfigurableTableJoined) {
            $this
                ->getSelect()
                ->join(
                    array('pl' => $this->getTable('catalog/product')),
                    "pl.entity_id = main_table.product_id",
                    array('product_type' => 'type_id')
                )
                ->where('type_id = ?', Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE);

            $this->_isProductConfigurableTableJoined = true;
        }
        return $this;
    }

    /**
     * Get only items collection being a configurable product
     *
     * @return $this
     */
    public function getProductsWithoutConfigurable()
    {
        if (!$this->_isProductTableJoined) {
            $this
                ->getSelect()
                ->join(
                    array('pl' => $this->getTable('catalog/product')),
                    "pl.entity_id = main_table.product_id",
                    array('product_type' => 'type_id')
                )
                ->where('type_id <> ?', Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE);

            $this->_isProductTableJoined = true;
        }
        return $this;
    }
}