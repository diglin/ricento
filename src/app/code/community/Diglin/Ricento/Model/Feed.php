<?php
/**
 * Diglin GmbH - Switzerland
 *
 * @author      Sylvain Rayé <sylvain.raye at diglin.com>
 * @category    Ricento
 * @package     Ricento
 * @copyright   Copyright (c) 2011-2015 Diglin (http://www.diglin.com)
 */

/**
 * Class Diglin_Ricento_Model_Feed
 */
class Diglin_Ricento_Model_Feed extends Mage_AdminNotification_Model_Feed
{
    const XML_USE_HTTPS_PATH    = 'ricento/adminnotification/use_https';
    const XML_FEED_URL_PATH     = 'ricento/adminnotification/feed_url';
    const XML_FREQUENCY_PATH    = 'ricento/adminnotification/frequency';

    public static function check()
    {
        return Mage::getModel('diglin_ricento/feed')->checkUpdate();
    }

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://')
                . Mage::getStoreConfig(self::XML_FEED_URL_PATH);
        }
        return $this->_feedUrl;
    }

    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return Mage::getStoreConfig(self::XML_FREQUENCY_PATH) * 3600;
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('ricento_notifications_lastcheck');
    }

    /**
     * Set last update time (now)
     *
     * @return Mage_AdminNotification_Model_Feed
     */
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'ricento_notifications_lastcheck');
        return $this;
    }

}