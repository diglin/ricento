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

use Diglin\Ricardo\Core\Helper;
use Diglin\Ricardo\Enums\Article\InternalReferenceType;
use Diglin\Ricardo\Enums\PictureExtension;
use Diglin\Ricardo\Enums\System\CategoryBrandingFilter;
use Diglin\Ricardo\Managers\Sell\Parameter\ArticleDeliveryParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\ArticleDescriptionParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\ArticleInformationParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\ArticleInternalReferenceParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\ArticlePictureParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\InsertArticleParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\CloseArticleParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\DeletePlannedArticleParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\GetArticleFeeParameter;
use Diglin\Ricardo\Managers\Sell\Parameter\BaseInsertArticleWithTrackingParameter;

/**
 * Products_Listing_Item Model
 *
 * @method int    getProductId()
 * @method string getType()
 * @method int    getParentItemId()
 * @method int    getParentProductId()
 * @method int    getRicardoArticleId()
 * @method int    getQtyInventory()
 * @method int    getProductsListingId()
 * @method int    getSalesOptionsId()
 * @method int    getRuleId()
 * @method int    getIsPlanned()
 * @method string getStatus()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method bool   getReload()
 * @method int    getStoreId()
 * @method bool   getLoadFallbackOptions()
 * @method Diglin_Ricento_Model_Products_Listing_Item setProductId(int $productId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setType(string $type)
 * @method Diglin_Ricento_Model_Products_Listing_Item setParentItemId(int $parentItemId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setParentProductId(int $parentProductId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setRicardoArticleId(int $ricardoArticleId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setQtyInventory(int $inventoryQty)
 * @method Diglin_Ricento_Model_Products_Listing_Item setProductsListingId(int $productListingId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setSalesOptionsId(int $salesOptionsId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setRuleId(int $ruleIid)
 * @method Diglin_Ricento_Model_Products_Listing_Item setIsPlanned(int $isPlanned)
 * @method Diglin_Ricento_Model_Products_Listing_Item setStatus(string $status)
 * @method Diglin_Ricento_Model_Products_Listing_Item setAdditionalData(string $additionalData)
 * @method Diglin_Ricento_Model_Products_Listing_Item setCreatedAt(DateTime $createdAt)
 * @method Diglin_Ricento_Model_Products_Listing_Item setUpdatedAt(DateTime $updatedAt)
 * @method Diglin_Ricento_Model_Products_Listing_Item setReload(bool $reload)
 * @method Diglin_Ricento_Model_Products_Listing_Item setStoreId(int $storeId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setDefaultStoreId(int $defaultStoreId)
 * @method Diglin_Ricento_Model_Products_Listing_Item setLoadFallbackOptions(bool $loadFallbackOptions)
 */
class Diglin_Ricento_Model_Products_Listing_Item extends Mage_Core_Model_Abstract
{
    /**
     * @var Diglin_Ricento_Model_Sales_Options
     */
    protected $_salesOptions;

    /**
     * @var Diglin_Ricento_Model_Rule
     */
    protected $_shippingPaymentRule;

    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'products_listing_item';

    /**
     * Parameter name in event
     * In observe method you can use $observer->getEvent()->getObject() in this case
     * @var string
     */
    protected $_eventObject = 'products_listing_item';

    /**
     * @var Diglin_Ricento_Model_Products_Listing
     */
    protected $_productsListing;

    /**
     * @var Diglin_Ricento_Model_Products_Listing_Item_Product
     */
    protected $_itemProduct;

    /**
     * @var array
     */
    protected $_storesLang = array();

    /**
     * Products_Listing_Item Constructor
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('diglin_ricento/products_listing_item');
    }

    /**
     * @return Diglin_Ricento_Model_Products_Listing_Item
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        if ($this->hasDataChanges() && !$this->dataHasChangedFor('status')
            && !in_array($this->getStatus(), array(Diglin_Ricento_Helper_Data::STATUS_LISTED, Diglin_Ricento_Helper_Data::STATUS_SOLD))
        ) {
            $this->setStatus(Diglin_Ricento_Helper_Data::STATUS_PENDING);
        }

        if ($this->getType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $this->setStatus('');
        }

        $gmtDate = Mage::getSingleton('core/date')->gmtDate();
        $this->setUpdatedAt($gmtDate);

        if ($this->isObjectNew()) {
            $this->setCreatedAt($gmtDate);
        }

        return $this;
    }

    /**
     * @return Mage_Core_Model_Abstract
     * @throws Exception
     */
    protected function _afterSave()
    {
        if ($this->hasDataChanges() && $this->getType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $childCollection = $this->getCollection()
                ->addFieldToFilter('products_listing_id', $this->getProductsListingId())
                ->addFieldToFilter('parent_item_id', $this->getId())
                ->addFieldToFilter('ricardo_article_id', array('null' => 1));

            /* @var $child Diglin_Ricento_Model_Products_Listing_Item */
            foreach ($childCollection->getItems() as $child) {
                $child
                    ->setRuleId($this->getRuleId())
                    ->setSalesOptionsId($this->getSalesOptionsId())
                    ->save();
            }
        }

        return parent::_afterSave();
    }

    /**
     * @return $this|Mage_Core_Model_Abstract
     */
    protected function _afterDeleteCommit()
    {
        if ($this->getSalesOptionsId()) {
            $this->getSalesOptions()->delete();
        }

        if ($this->getRuleId()) {
            $this->getShippingPaymentRule()->delete();
        }

        parent::_afterDeleteCommit();
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultStoreId()
    {
        $this->_prepareStoresLanguage();
        return $this->getData('default_store_id');
    }

    /**
     * @return Diglin_Ricento_Model_Products_Listing_Item_Product
     */
    public function getProduct()
    {
        if (empty($this->_itemProduct)) {
            $this->_itemProduct = Mage::getModel('diglin_ricento/products_listing_item_product');
            $this->_itemProduct
                ->setProductListingItem($this)
                ->setDefaultStoreId($this->getDefaultStoreId()); // fallback for language
        }

        $reload = $this->getReload();
        if ($reload) {
            // To use with precaution - it's a bottleneck
            $this->_itemProduct->reset();
            $this->setReload(false);
        }

        return $this->_itemProduct
            ->setStoreId($this->getStoreId())
            ->setProductId($this->getProductId());
    }

    /**
     * @return Diglin_Ricento_Model_Sales_Options
     */
    public function getSalesOptions()
    {
        if (!$this->_salesOptions) {
            $this->_salesOptions = Mage::getModel('diglin_ricento/sales_options');
            if ($this->getSalesOptionsId()) {
                $this->_salesOptions->load($this->getSalesOptionsId());
            } elseif ($this->getLoadFallbackOptions()) {
                $this->_salesOptions = $this->getProductsListing()->getSalesOptions();
            }
        }

        return $this->_salesOptions;
    }

    /**
     * @return Diglin_Ricento_Model_Rule
     */
    public function getShippingPaymentRule()
    {
        if (!$this->_shippingPaymentRule) {
            $this->_shippingPaymentRule = Mage::getModel('diglin_ricento/rule');
            if ($this->getRuleId()) {
                $this->_shippingPaymentRule->load($this->getRuleId());
            } elseif ($this->getLoadFallbackOptions()) {
                $this->_shippingPaymentRule = $this->getProductsListing()->getShippingPaymentRule();
            }
        }
        return $this->_shippingPaymentRule;
    }

    /**
     * @return Diglin_Ricento_Model_Products_Listing
     */
    public function getProductsListing()
    {
        if (empty($this->_productsListing) || !$this->_productsListing->getId()) {
            $this->_productsListing = Mage::getModel('diglin_ricento/products_listing')->load($this->getProductsListingId());
        }
        return $this->_productsListing;
    }

    /**
     * @return int
     */
    public function getCategory()
    {
        $ricardoCategoryId = $this->getSalesOptions()->getRicardoCategory();
        if ($ricardoCategoryId < 0) {
            $catIds = $this->getProduct()->getCategoryIds($this->getBaseProductId());
            if (!$catIds) {
                return false;
            }
            foreach ($catIds as $id) {
                $category = Mage::getModel('catalog/category')->load($id);
                $ricardoCategoryId = $category->getRicardoCategory();
                if ($ricardoCategoryId) {
                    break;
                }
            }
        }

        return (int) $ricardoCategoryId;
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getMagentoProduct()
    {
        return $this->getProduct()->getMagentoProduct();
    }

    /**
     * Get base Product Id in case of configurable product
     * @return int
     */
    public function getBaseProductId()
    {
        if ($this->getParentProductId()) {
            $productId = $this->getParentProductId();
        } else {
            $productId = $this->getProductId();
        }
        return $productId;
    }

    /**
     * @param bool $sub
     * @return string
     */
    public function getProductTitle($sub = true)
    {
        return $this->getProduct()->getTitle($this->getBaseProductId(), $this->getStoreId(), $sub);
    }

    /**
     * @param bool $sub
     * @return array|string
     */
    public function getProductSubtitle($sub = true)
    {
        // Get subtitle from configurable options
        if ($this->getParentProductId()) {
            $subtitles = array();
            foreach ($this->getAdditionalData()->getOptions() as $option) {
                $subtitles[] = $option['subtitle'];
            }
            return implode(' | ', $subtitles); // @todo make it for the different store views
        }
        return $this->getProduct()->getSubtitle($this->getProductId(), $this->getStoreId(), $sub);
    }

    /**
     * @param bool $sub
     * @return mixed|string
     */
    public function getProductDescription($sub = true)
    {
        $description = $this->getProduct()->getDescription($this->getBaseProductId(), $this->getStoreId(), $sub);
        $description = (Mage::helper('diglin_ricento')->isNl2BrEnabled($this->getStoreId())) ? nl2br($description) : $description;

        return strip_tags($description, implode('', Mage::helper('diglin_ricento')->getAllowedTags()));
    }

    /**
     * Return the product price in CHF
     *
     * @return float
     */
    public function getProductPrice($convert = true)
    {
        $priceOptions = array();

        if ($this->getParentProductId()) {
            foreach ($this->getAdditionalData()->getOptions() as $option) {
                if (isset($option['pricing_value'])) {
                    $priceOptions[] = $option;
                }
            }
        }

        // We take the price from default store view of the current Product Listing Website and parent product if relevant
        $productPrice = $this->getProduct()
            ->setStoreId($this->getDefaultStoreId())
            ->setPriceOptions($priceOptions)
            ->getPrice($convert);

        return $productPrice;
    }

    /**
     * @return int
     */
    public function getProductQty()
    {
        $qtyMax = Diglin_Ricento_Model_Validate_Products_Item::QTY_MAX;

        if ($this->getSalesOptions()->getStockManagement() == -1
            || $this->getSalesOptions()->getStockManagementQtyType() == Diglin_Ricento_Helper_Data::INVENTORY_QTY_TYPE_PERCENT) {

            $qty = $this->getProduct()->getQty();

            if ($qty <= 0) {
                return 0;
            }

            if ($this->getSalesOptions()->getStockManagement() == -1) {
                return ($qty > $qtyMax) ? $qtyMax : $qty;
            }

            $qty = $this->getProduct()->getPercentQty($qty, $this->getSalesOptions()->getStockManagement(), Diglin_Ricento_Helper_Data::INVENTORY_QTY_TYPE_PERCENT);

            return ($qty > $qtyMax) ? $qtyMax : $qty;
        } else {
            return $this->getSalesOptions()->getStockManagement();
        }
    }

    /**
     * @return int
     */
    public function getProductSku()
    {
        return $this->getProduct()->getSku($this->getProductId());
    }

    /**
     * @return string
     */
    public function getProductCondition()
    {
        $sourceCondition = $this->getSalesOptions()->getProductConditionSourceAttributeCode();
        if (!empty($sourceCondition)) {
            $condition = $this->getProduct()->getCondition($this->getBaseProductId());
            if (!empty($condition)) {
                return $condition;
            }
        }

        return $this->getSalesOptions()->getProductCondition();
    }

    /**
     * @return string
     */
    public function getInternalReference()
    {
        return Mage::helper('diglin_ricento')->generateInternalReference($this);
    }

    /**
     * Define a list of store IDs for each supported and expected language
     *
     * @return array
     */
    protected function _prepareStoresLanguage()
    {
        if (!$this->_storesLang) {
            $defaultLang = null;
            $publishLanguages = $this->getProductsListing()->getPublishLanguages();

            if ($publishLanguages == 'all') {
                $languages = Mage::helper('diglin_ricento')->getSupportedLang();
                $defaultLang = $this->getProductsListing()->getDefaultLanguage();
                foreach ($languages as $language) {
                    $this->_storesLang[$language] = $this->getLangStoreId($language);
                    if ($defaultLang == $language) {
                        $this->setDefaultStoreId($this->_storesLang[$language]);
                    }
                }
            } else {
                $this->_storesLang[$publishLanguages] = $this->getLangStoreId($publishLanguages);
                $this->setDefaultStoreId($this->_storesLang[$publishLanguages]);
            }
        }

        return $this->_storesLang;
    }

    /**
     * @param string $language
     * @return int
     * @throws Mage_Core_Exception
     */
    public function getLangStoreId($language)
    {
        $method = 'getLangStoreId' . ucwords($language);
        $storeId = $this->getProductsListing()->$method();
        if (is_null($storeId)) {
            $storeId = Mage::app()->getWebsite($this->getProductsListing()->getWebsiteId())->getDefaultStore()->getId();
        }
        return (int) $storeId;
    }

    /**
     * Prepare the InsertArticle Parameter
     *
     * @deprecated
     * @return InsertArticleParameter
     */
    public function getInsertArticleParameter()
    {
        $insertArticleParameter = new InsertArticleParameter();

        $this->setLoadFallbackOptions(true);

        $this->_shippingPaymentRule = $this->getShippingPaymentRule();
        $this->_salesOptions = $this->getSalesOptions();

        //** Article Description

        foreach ($this->_prepareStoresLanguage() as $language => $storeId) {
            $this->setStoreId($storeId);
            $insertArticleParameter->setDescriptions($this->_getArticleDescriptionsParameter($language));
        }

        //** Article Images

        $this->_setInsertArticlePictures($insertArticleParameter);

        $insertArticleParameter
            ->setAntiforgeryToken($this->_getAntiforgeryToken())
            ->setArticleInformation($this->_getArticleInformationParameter())
            ->setIsUpdateArticle(false);

        $this->setLoadFallbackOptions(false);

        return $insertArticleParameter;
    }

    /**
     * Prepare the InsertArticle Parameter
     *
     * @return BaseInsertArticleWithTrackingParameter
     */
    public function getBaseInsertArticleWithTracking()
    {
        $baseInsert = new BaseInsertArticleWithTrackingParameter();

        $this->setLoadFallbackOptions(true);

        $this->_shippingPaymentRule = $this->getShippingPaymentRule();
        $this->_salesOptions = $this->getSalesOptions();

        //** Article Description

        foreach ($this->_prepareStoresLanguage() as $language => $storeId) {
            $this->setStoreId($storeId);
            $baseInsert->setDescriptions($this->_getArticleDescriptionsParameter($language));
        }

        //** Article Images

        $this->_setInsertArticlePictures($baseInsert);

        $baseInsert
            ->setArticleInformation($this->_getArticleInformationParameter())
            ->setIsUpdateArticle(false)
            ->setCorrelationKey(Helper::guid());

        $this->setLoadFallbackOptions(false);

        return $baseInsert;
    }

    /**
     * @param InsertArticleParameter $insertArticleParameter
     * @return $this
     */
    protected function _setInsertArticlePictures(InsertArticleParameter $insertArticleParameter)
    {
        $useConfigurableSimpleProductPicture = Mage::helper('diglin_ricento')->useConfigurableSimpleProductPicture();

        $images = (array) $this->getProduct()
            ->getImages(
                ($useConfigurableSimpleProductPicture) ? $this->getProductId() : $this->getBaseProductId()
            );

        $i = 0;
        $hash = array();

        foreach ($images as $image) {

            $filename = $image['filepath'];
            if (!empty($filename)) {
                try {
                    $filename = Mage::helper('diglin_ricento/image')->prepareRicardoPicture($filename);
                } catch (Exception $e) {
                    Mage::logException($e);
                    $filename = null;
                }
            }

            if (!$filename) {
                continue;
            }

            if ($filename == 'no_selection') {
                continue;
            }

            if ($i >= 10) { // Do not set more than 10 pictures
                break;
            };

            $hashImage = md5($filename);
            if (!isset($hash[$hashImage]) && file_exists($filename)) {

                // Prepare picture to set the content as byte array for the webservice
                $imageExtension = Helper::getPictureExtension($filename);

                if ($imageExtension) {
                    $picture = new ArticlePictureParameter();
                    $picture
                        ->setPictureInBase64(base64_encode(file_get_contents($filename)))
                        // we encode in Json to minimize memory consumption
//                        ->setPictureBytes(json_encode(array_values(unpack('C*', file_get_contents($filename)))))
                        ->setPictureExtension($imageExtension)
                        ->setPictureIndex(++$i);

                    $insertArticleParameter->setPictures($picture);
                }

                unset($picture);
                $hash[$hashImage] = true;
            }
        }

        return $this;
    }

    /**
     * Prepare the Article Delivery Parameter
     *
     * @return ArticleDeliveryParameter
     */
    protected function _getArticleDeliveryParameter()
    {
        $shippingPrice = $this->_shippingPaymentRule->getShippingPrice();
        $method = $this->_shippingPaymentRule->getShippingMethod();
        $freeShipping = false;
        if (floatval($shippingPrice) <= 0 && $method != 8) { // $method = 8 no delivery, pick up by the buyer
            $freeShipping = true;
        }

        $delivery = new ArticleDeliveryParameter();

        $delivery
            // required
            ->setDeliveryCost($this->_shippingPaymentRule->getShippingPrice())
            ->setIsDeliveryFree($freeShipping)
            ->setDeliveryId($method)
            ->setIsCumulativeShipping($this->_shippingPaymentRule->getShippingCumulativeFee())
            // optional
            ->setDeliveryPackageSizeId($this->_shippingPaymentRule->getShippingPackage());

        return $delivery;
    }

    /**
     * Prepare the Internal Reference Parameter
     *
     * @return ArticleInternalReferenceParameter
     */
    protected function _getInternalReferencesParameter()
    {
        $internalReferences = new ArticleInternalReferenceParameter();

        $internalReferences
            ->setInternalReferenceTypeId(InternalReferenceType::SELLERSPECIFIC)
            ->setInternalReferenceValue($this->getInternalReference());

        return $internalReferences;
    }

    /**
     * Prepare the Article Information
     *
     * @return ArticleInformationParameter
     */
    protected function _getArticleInformationParameter()
    {
        $helper = Mage::helper('diglin_ricento');
        $paymentConditions = array();
        $paymentMethods = (array)$this->_shippingPaymentRule->getPaymentMethods();
        $salesType = $this->getSalesOptions()->getSalesType();

        foreach ($paymentMethods as $paymentMethod) {
            $paymentConditions[] = $this->_getPaymentConditionId($paymentMethod);
        }

        $untilSoldOut = ((int)$this->getSalesOptions()->getScheduleReactivation() === Diglin_Ricento_Model_Config_Source_Sales_Reactivation::SOLDOUT);
        $customTemplate = ($this->getSalesOptions()->getCustomizationTemplate() >= 0) ? $this->getSalesOptions()->getCustomizationTemplate() : null;

        $articleInformation = new ArticleInformationParameter();
        $articleInformation
            // required
            ->setArticleConditionId($this->getProductCondition())
            ->setArticleDuration(($this->getSalesOptions()->getSchedulePeriodDays() * 24 * 60)) // In minutes
            ->setAvailabilityId($this->_shippingPaymentRule->getShippingAvailability())
            ->setCategoryId($this->getCategory())
            ->setInitialQuantity($this->getProductQty())
            ->setIsCustomerTemplate(((!is_null($customTemplate)) ? true : false))
            ->setMainPictureId(1)
            ->setMaxRelistCount((!$untilSoldOut) ? $this->getSalesOptions()->getScheduleReactivation() : 0)
            ->setWarrantyId($this->getSalesOptions()->getProductWarranty())
            ->setDeliveries($this->_getArticleDeliveryParameter())
            // optional
            ->setInternalReferences($this->_getInternalReferencesParameter())
            ->setPaymentConditionIds($paymentConditions)
            ->setPaymentMethodIds($paymentMethods)
            ->setTemplateId($customTemplate);

        /**
         * Start Date is mandatory for auction but optional for buy now sales type
         */
        $startingDate = $helper->getStartingDate($this);
        if (!is_null($startingDate)) {
            $articleInformation->setStartDate($helper->getJsonDate($startingDate));
        }

        if ($salesType == Diglin_Ricento_Model_Config_Source_Sales_Type::AUCTION) {
            $articleInformation
                ->setIncrement($this->getSalesOptions()->getSalesAuctionIncrement())
                ->setStartPrice($this->getSalesOptions()->getSalesAuctionStartPrice());
        }

        if ($salesType == Diglin_Ricento_Model_Config_Source_Sales_Type::BUYNOW
            || $this->getSalesOptions()->getSalesAuctionDirectBuy()
        ) {
            $articleInformation->setBuyNowPrice($this->getProductPrice());
        }

        if ($salesType == Diglin_Ricento_Model_Config_Source_Sales_Type::BUYNOW) {
            $articleInformation->setIsRelistSoldOut($untilSoldOut);
        }

        // required
        $articleInformation->setPromotionIds($this->getSalesOptions()->getPromotionIds());

        return $articleInformation;
    }



    /**
     * Prepare Article Description Parameter
     *
     * @param string $lang
     * @return ArticleDescriptionParameter
     */
    protected function _getArticleDescriptionsParameter($lang = Diglin_Ricento_Helper_Data::DEFAULT_SUPPORTED_LANG)
    {
        $descriptions = new ArticleDescriptionParameter();

        $descriptions
            // required
            ->setArticleTitle($this->getProductTitle())
            ->setArticleDescription($this->getProductDescription())
            ->setLanguageId(Mage::helper('diglin_ricento')->getRicardoLanguageIdFromLocaleCode($lang))
            // optional
            ->setArticleSubtitle($this->getProductSubtitle())
            ->setDeliveryDescription($this->_shippingPaymentRule->getShippingDescription($lang))
            ->setPaymentDescription($this->_shippingPaymentRule->getPaymentDescription($lang))
            ->setWarrantyDescription($this->_salesOptions->getProductWarrantyDescription($lang));

        return $descriptions;
    }

    /**
     * @param int $paymentMethod
     * @return null|int
     */
    protected function _getPaymentConditionId($paymentMethod)
    {
        $conditions = (array) Mage::getSingleton('diglin_ricento/api_services_system')
            ->getPaymentConditionsAndMethods();

        foreach ($conditions as $condition) {
            if (isset($condition['PaymentMethods']) && !empty($condition['PaymentMethods'])) {
                foreach ($condition['PaymentMethods'] as $method) {
                    if (isset($method['PaymentMethodId']) && $method['PaymentMethodId'] == (int)$paymentMethod) {
                        return (int)$condition['PaymentConditionId'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return bool|CloseArticleParameter
     */
    public function getCloseArticleParameter()
    {
        if (!$this->getRicardoArticleId()) {
            return false;
        }

        $closeParameter = new CloseArticleParameter();
        $closeParameter
            ->setAntiforgeryToken($this->_getAntiforgeryToken())
            ->setArticleId($this->getRicardoArticleId());

        return $closeParameter;
    }

    /**
     * @return bool|DeletePlannedArticleParameter
     */
    public function getDeleteArticleParameter()
    {
        if (!$this->getRicardoArticleId()) {
            return false;
        }

        $deleteParameter = new DeletePlannedArticleParameter();
        $deleteParameter
            ->setAntiforgeryToken($this->_getAntiforgeryToken())
            ->setPlannedArticleId($this->getRicardoArticleId());

        return $deleteParameter;
    }

    /**
     * @return Diglin_Ricento_Model_Api_Services_Security
     */
    protected function _getAntiforgeryToken()
    {
        return Mage::helper('diglin_ricento/api')->getAntiforgeryToken($this->getProductsListing()->getWebsiteId());
    }

    /**
     * Get the additional data and decode the json string
     *
     * @return array|Varien_Object
     */
    public function getAdditionalData()
    {
        $data = $this->getData('additional_data');
        if ($data) {
            return new Varien_Object(Mage::helper('core')->jsonDecode($data));
        }
        return array();
    }

    /**
     * @return GetArticleFeeParameter
     */
    public function getArticleFeeDetails($excludeListingFees = false)
    {
        $helper = Mage::helper('diglin_ricento');

        $this->setLoadFallbackOptions(true);
        $salesType = $this->getSalesOptions()->getSalesType();

        $articleFeeParameter = new GetArticleFeeParameter();
        $articleFeeParameter
            ->setArticleCondition($this->getProductCondition())
            ->setCategoryId(Diglin_Ricento_Model_Products_Category_Mapping::ROOT_CATEGORY_ID)
            ->setExcludeListingFees($excludeListingFees)
            ->setInitialQuantity($this->getProductQty())
            ->setPictureCount(0) //@todo check if it is really relevant to send this information as we save some computation, there is no influence on final price
            ->setPromotionIds($this->getSalesOptions()->getPromotionIds())
            ->setStartDate(Helper::getJsonDate($helper->getStartingDate($this)));

        if ($salesType == Diglin_Ricento_Model_Config_Source_Sales_Type::AUCTION) {
            $articleFeeParameter->setStartPrice($this->getSalesOptions()->getSalesAuctionStartPrice());
        }

        if ($salesType == Diglin_Ricento_Model_Config_Source_Sales_Type::BUYNOW || $this->getSalesOptions()->getSalesAuctionDirectBuy()) {
            $articleFeeParameter->setBuyNowPrice($this->getProductPrice());
        }

        $this->setLoadFallbackOptions(false);

        return $articleFeeParameter;
    }
}