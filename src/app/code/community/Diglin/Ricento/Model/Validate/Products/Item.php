<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author Sylvain Rayé <sylvain.raye at diglin.com>
 * @category    Ricento
 * @package     Diglin_Ricardo
 * @copyright   Copyright (c) 2011-2014 Diglin (http://www.diglin.com)
 */
class Diglin_Ricento_Model_Validate_Products_Item extends Zend_Validate_Abstract
{
    const LENGTH_PRODUCT_TITLE = 40;
    const LENGTH_PRODUCT_SUBTITLE = 60;
    const LENGTH_PRODUCT_DESCRIPTION = 65000;

    const BUYNOW_MINPRICE_FIXPRICE = 0.05;
    const BUYNOW_MAXPRICE_FIXPRICE = 2999.95;
    const BUYNOW_MINPRICE_AUCTIONPRICE = 0.1;

    const PERIOD_DAYS_MIN = 1; // in days
    const PERIOD_DAYS_MAX = 10; // in days

    protected $_errors = array();
    protected $_warnings = array();
    protected $_success = array();

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param Diglin_Ricento_Model_Products_Listing_Item $item
     * @param array $stores
     * @return boolean
     * @throws Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($item, $stores = array(Mage_Core_Model_APP::ADMIN_STORE_ID))
    {
        if (!$item instanceof Diglin_Ricento_Model_Products_Listing_Item) {
            return false;
        }

        $item->setLoadFallbackOptions(true);
        $helper = Mage::helper('diglin_ricento');

        // Validate product content for each available store

        foreach ($stores as $store) {

            $item
                //->setReload(true) // @todo reload is a bottleneck but we need it to use it to get correct value of "HasOptions" below
                ->setStoreId($store);

            $storeCode = Mage::app()->getStore($store)->getName();

            // Validate title

            $strLen = new Zend_Validate_StringLength(array('min' => 1, 'max' => self::LENGTH_PRODUCT_TITLE));
            if (!$strLen->isValid($item->getProductTitle(false))) {
                // warning - content will be cut when exporting to ricardo
                $this->_warnings[] = $helper->__('Product Title will be cut after %s characters when published on ricardo.ch for store "%s"', self::LENGTH_PRODUCT_TITLE, $storeCode);
            }

            // Validate subtitle

            $strLen = new Zend_Validate_StringLength(array('max' => self::LENGTH_PRODUCT_SUBTITLE));
            if (!$strLen->isValid($item->getProductSubtitle(false))) {
                // warning - content will be cut when exporting to ricardo
                $this->_warnings[] = $helper->__('Product Subtitle will be cut after %s characters when published on ricardo.ch for store "%s"', self::LENGTH_PRODUCT_SUBTITLE, $storeCode);
            }

            // Validate description

            $strLen = new Zend_Validate_StringLength(array('min' => 1, 'max' => self::LENGTH_PRODUCT_DESCRIPTION));
            if (!$strLen->isValid($item->getProductDescription(false))) {
                // warning - content will be cut when exporting to ricardo
                $this->_warnings[] = $helper->__('Product Description will be cut after %s characters when published on ricardo.ch for store "%s"', self::LENGTH_PRODUCT_DESCRIPTION, $storeCode);
            }

            // Validate custom options

            if (!$item->getMagentoProduct()->hasOptions()) {
                // warning - no option will be send to ricardo.ch
                $this->_warnings[] = $helper->__('Custom Options are not supported. Those won\'t be synchronized into ricardo.ch for store "%s"', Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY, $storeCode);
            }
        }

        // Validate products listing item


        // Validate Inventory - In Stock or not? Enough Qty or not?

        $salesOptionsStockManagement = $item->getSalesOptions()->getStockManagement();
        $stockItem = $item->getProduct()->getStockItem();

        if ($stockItem->getManageStock()) {
            if ($salesOptionsStockManagement == -1) {
                $qty = 1;
                // if stock managed and qty > 0 => ok
                // if stock not managed => ok (default qty will be set to 1)
            } else {
                $qty = $salesOptionsStockManagement;
                // if stock managed, check there is enough quantity compared to $salesOptionsStockManagement
                // if stock is not managed => ok
            }

            if (!$item->getProduct()->checkQty($qty) || !$stockItem->getIsInStock()) {
                // Error - Qty not available or not in stock
                $this->_errors[] = $helper->__('The product or its associated products is/are not in stock or doesn\'t have enough quantity in stock  for store "%s"', $storeCode);
            }
        }

        // Validate the currency

        $currencyCode = Mage::app()->getWebsite($item->getProductsListing()->getWebsiteId())->getBaseCurrencyCode();
        if ($currencyCode != Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY) {
            // Warning - Ricardo supports only CHF currency
            $this->_warnings[] = $helper->__('Only %s currency is supported. No conversion will be done.', Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY);
        }

        // Validate Category exists

        $ricardoCategoryId = $item->getCategory();
        if (!$ricardoCategoryId) {
            // error - category cannot be empty
            $this->_errors[] = $helper->__('You MUST define a ricardo category for this product. Check that you set it at products listing level or at Magento category level.');
        }

        // Validate Payment and Shipping Rule

        $methodValidator = new Diglin_Ricento_Model_Validate_Rules_Methods();
        $rules = $item->getShippingPaymentRule();
        $methods = array(
            'shipping' => $rules->getShippingMethod(),
            'payment' => $rules->getPaymentMethods()
        );

        if (!$methodValidator->isValid($methods)) {
            // Error - combination respect mandatory
            $this->_errors[] = $helper->__('Payment and/or Shipping combination are not correct.') . '<br>' . print_r($methodValidator->getMessages(), true);
        }

        // Validate price against buy now price > 0.05 or 0.1

        $salesOptions = $item->getSalesOptions();
        $productPrice = $item->getProduct()->getPrice();
        if ($salesOptions->getSalesType() == Diglin_Ricento_Model_Config_Source_Sales_Type::AUCTION && $salesOptions->getSalesAuctionDirectBuy()) {
            $startPrice = $salesOptions->getSalesAuctionStartPrice();
            $minPrice = ($startPrice < 0.1) ? 0.1 : $startPrice;

            $greatherThanValidator  = new Zend_Validate_GreaterThan(array('min' => $minPrice));

            if (!$greatherThanValidator->isValid($productPrice)) {
                // Error - Price not allowed
                $this->_errors[] = $helper->__('Product Price of %s is incorrect for this starting price %s.', $productPrice, $minPrice);
            }
        }

        if ($salesOptions->getSalesType() == Diglin_Ricento_Model_Config_Source_Sales_Type::BUY_NOW) {
            $betweenValidator  = new Zend_Validate_Between(
                array(
                    'min' => self::BUYNOW_MINPRICE_FIXPRICE,
                    'max' => self::BUYNOW_MAXPRICE_FIXPRICE,
                    'inclusive' => true
                )
            );
            if (!$betweenValidator->isValid($productPrice)) {
                // Error - Price not allowed
                $this->_errors[] = $helper->__('Product Price of %s is incorrect for a direct sales. Price must be between %s and %s.', $productPrice, self::BUYNOW_MINPRICE_FIXPRICE, self::BUYNOW_MAXPRICE_FIXPRICE);
            }
        }

        // Validate Ending Date

        $period = (int) $item->getSalesOptions()->getSchedulePeriodDays();
        $betweenValidator  = new Zend_Validate_Between(
            array(
                'min' => self::PERIOD_DAYS_MIN,
                'max' => self::PERIOD_DAYS_MAX,
                'inclusive' => true
            )
        );

        if (!$betweenValidator->isValid($period)) {
            // Error - Period too long or too short
            $this->_errors[] = $helper->__('The ending date is too early or too late. Minimum period allow %s days - Maximum period allowed %s days', self::PERIOD_DAYS_MIN, self::PERIOD_DAYS_MAX);
        }

        // Validate picture - warning if promotions exists but no picture

        $productResource = Mage::getResourceModel('catalog/product');
        $assignedImages = $productResource->getAssignedImages($item->getMagentoProduct(), $stores);
        if (empty($assignedImages) && ($salesOptions->getPromotionSpace() || $salesOptions->getPromotionStartPage())) {
            // Warning - No promotion possible if no image in the product
            $this->_warnings[] = $helper->__('You cannot use the privilege spaces as you do not have any pictures for this product.');
        }

        $item->setLoadFallbackOptions(false);

        if (count($this->_errors)) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @return array
     */
    public function getWarnings()
    {
        return $this->_warnings;
    }

    /**
     * @return array
     */
    public function getSuccess()
    {
        return $this->_success;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return array(
            'errors' => $this->_errors,
            'warnings' => $this->_warnings,
            'success' => $this->_success,
        );
    }
}