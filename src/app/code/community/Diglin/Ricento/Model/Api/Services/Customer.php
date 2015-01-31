<?php
/**
 * ricardo.ch AG - Switzerland
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @category    Diglin
 * @package     Diglin_Ricento
 * @copyright   Copyright (c) 2014 ricardo.ch AG (http://www.ricardo.ch)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Class Diglin_Ricento_Model_Api_Services_Customer
 */
class Diglin_Ricento_Model_Api_Services_Customer extends Diglin_Ricento_Model_Api_Services_Abstract
{
    /**
     * @var string
     */
    protected $_serviceName = 'customer';

    /**
     * @var string
     */
    protected $_model = '\Diglin\Ricardo\Managers\Customer';

    /**
     * Overwritten just to get the class/method auto completion
     * Be aware that using directly this method to use the methods of the object instead of using
     * the magic methods of the abstract (__call, __get, __set) will prevent to use the cache of Magento
     * but also some logic related to the securtiy token
     *
     * @return \Diglin\Ricardo\Managers\Customer
     */
    public function getServiceModel()
    {
        return parent::getServiceModel();
    }
}