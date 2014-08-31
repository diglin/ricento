<?php

/**
 * Diglin GmbH - Switzerland
 *
 * @author Sylvain Rayé <sylvain.raye at diglin.com>
 * @category    Diglin
 * @package     Diglin_Ricento
 * @copyright   Copyright (c) 2011-2014 Diglin (http://www.diglin.com)
 */
class Diglin_Ricento_Model_Config_Source_Sync_Type extends Diglin_Ricento_Model_Config_Source_Abstract
{
    public function toOptionHash()
    {
        $helper = Mage::helper('diglin_ricento');

        return array(
            Diglin_Ricento_Model_Sync_Job::TYPE_CHECK_LIST => $helper->__('Products Check Job'),
            Diglin_Ricento_Model_Sync_Job::TYPE_LIST => $helper->__('List Job'),
            Diglin_Ricento_Model_Sync_Job::TYPE_STOP => $helper->__('Stop List Job')
        );
    }
}