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
class Diglin_Ricento_Helper_Price extends Mage_Core_Helper_Abstract
{
    /**
     * @var string
     */
    protected $_oldCurrency = null;

    /**
     * @var array
     */
    protected $_currencyCache = array();

    /**
     * Format the price with the ricardo supported currencies
     *
     * @param float $value
     * @param int $websiteId
     * @return string
     */
    public function formatPrice($value, $websiteId = null)
    {
        $this->startCurrencyEmulation($websiteId);

        $value = Mage::app()->getWebsite($websiteId)->getDefaultStore()->formatPrice($value);

        $this->stopCurrencyEmulation($websiteId);

        return $value;
    }

    /**
     * Emulate CHF currency in case the current store settings is different as the allowed currency/ies
     *
     * @param int $websiteId
     * @return $this
     */
    public function startCurrencyEmulation($websiteId = null)
    {
//        $partnerConfiguration = Mage::getSingleton('diglin_ricento/api_services_system')
//            ->setCurrentWebsite($websiteId)
//            ->getPartnerConfigurations();
//
//        if (isset($partnerConfiguration['CurrencyPrefix'])) {
//            $ricardoCurrency = $partnerConfiguration['CurrencyPrefix'];
//        } else {
//            $ricardoCurrency = Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY;
//        }

        $store = Mage::app()->getWebsite($websiteId)->getDefaultStore();

        if ($store->getCurrentCurrency()->getCode() != Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY) {
            $this->_oldCurrency = $store->getCurrentCurrency();
            $store->setCurrentCurrency(Mage::getModel('directory/currency')->load(Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY));
        }

        return $this;
    }

    /**
     * Revert the changes done regarding the currency of the current store
     *
     * @param int $websiteId
     * @return $this
     */
    public function stopCurrencyEmulation($websiteId = null)
    {
        Mage::app()->getWebsite($websiteId)->getDefaultStore()->setCurrentCurrency($this->_oldCurrency);
        $this->_oldCurrency = null;

        return $this;
    }

    /**
     * Calculate the price change depending on the type and value of the change to apply
     *
     * @param float|int $price
     * @param string $priceChangeType
     * @param float|int $priceChange
     * @return float
     */
    public function calculatePriceChange($price, $priceChangeType, $priceChange)
    {
        switch ($priceChangeType) {
            case Diglin_Ricento_Model_Config_Source_Sales_Price_Method::PRICE_TYPE_DYNAMIC_NEG:
                $price -= ($price * $priceChange / 100);
                break;
            case Diglin_Ricento_Model_Config_Source_Sales_Price_Method::PRICE_TYPE_DYNAMIC_POS:
                $price += ($price * $priceChange / 100);
                break;
            case Diglin_Ricento_Model_Config_Source_Sales_Price_Method::PRICE_TYPE_FIXED_NEG:
                $price -= $priceChange;
                break;
            case Diglin_Ricento_Model_Config_Source_Sales_Price_Method::PRICE_TYPE_FIXED_POS:
                $price += $priceChange;
                break;
            case Diglin_Ricento_Model_Config_Source_Sales_Price_Method::PRICE_TYPE_NOCHANGE:
            default:
                break;
        }

        return $price;
    }

    /**
     * Calculate configurable product selection price
     *
     * @param   array $priceInfo
     * @param   float $productPrice
     * @return  float
     */
    public function calcSelectionPrice($priceInfo, $productPrice)
    {
        if ($priceInfo['is_percent']) {
            $ratio = $priceInfo['pricing_value']/100;
            $price = $productPrice * $ratio;
        } else {
            $price = $priceInfo['pricing_value'];
        }
        return $price;
    }

    /**
     * @param float $price
     * @param null|int $websiteId
     * @param null|string $defaultCurrency
     * @return array
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function formatDoubleCurrency($price, $websiteId = null, $defaultCurrency = null)
    {
        $priceCurrentCurrency = null;
        $formattedPrice = array();
        $store = Mage::app()->getWebsite($websiteId)->getDefaultStore();

        if ($defaultCurrency == Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY) {
            // CHF Currency
            $formattedPrice['left_currency'] = $this->formatPrice($price, $websiteId);

            // Base Currency
            if ($store->getBaseCurrencyCode() != Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY) {
                $priceCurrency = $this->convert($price, Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY, $store->getBaseCurrencyCode(), $websiteId);
                $formattedPrice['right_currency'] = $store->formatPrice($priceCurrency);
            }
        } else {
            // Base Currency
            $formattedPrice['left_currency'] = $store->formatPrice($price);

            // CHF Currency
            if ($store->getBaseCurrencyCode() != Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY) {
                $priceCurrency = $this->convert($price, $store->getBaseCurrencyCode(), Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY, $websiteId);
                $formattedPrice['right_currency'] = $this->formatPrice($priceCurrency, $websiteId);
            }
        }

        return $formattedPrice;
    }

    /**
     * @param float $price
     * @param string $from
     * @param null|string $to
     * @return float|null
     * @throws Exception
     * @throws Mage_Core_Exception
     */
    public function convert($price, $from, $to = null, $websiteId = null)
    {
        $priceCurrency = null;
        $store = Mage::app()->getWebsite($websiteId)->getDefaultStore();

        try {
            if ($from == Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY && $to == $store->getBaseCurrencyCode()) {
                /**
                 * Magento cannot convert from CHF to Base Currency Code
                 */
                $rate = $this->getCurrency($store->getBaseCurrencyCode())->getRate($from);
                if ($rate) {
                    $priceCurrency = $price / $rate;
                } else {
                    throw new Exception(Mage::helper('directory')->__('Undefined rate from "%s-%s".',
                        Diglin_Ricento_Helper_Data::ALLOWED_CURRENCY,
                        $store->getBaseCurrencyCode())
                    );
                }
            } else {
                $priceCurrency = $this->getCurrency($from)->convert($price, $this->getCurrency($to));
            }
        } catch (Exception $e) {
            $priceCurrency = $this->__('NaN');
            Mage::log($e->__toString(), Zend_Log::ERR, Diglin_Ricento_Helper_Data::LOG_FILE, true);
        }

        return $priceCurrency;
    }

    /**
     * @param string $currencyCode
     * @return Mage_Directory_Model_Currency
     */
    public function getCurrency($currencyCode)
    {
        if (empty($this->_currencyCache[$currencyCode])) {
            $this->_currencyCache[$currencyCode] = Mage::getModel('directory/currency')->load($currencyCode);
        }

        return $this->_currencyCache[$currencyCode];
    }
}