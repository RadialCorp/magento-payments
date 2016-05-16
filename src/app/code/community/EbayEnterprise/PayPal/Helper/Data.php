<?php
/**
 * Copyright (c) 2013-2014 eBay Enterprise, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2014 eBay Enterprise, Inc. (http://www.ebayenterprise.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This class doesn't do anything testworthy
 * @codeCoverageIgnore
 */
class EbayEnterprise_PayPal_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    const STATUS_HANDLER_PATH = 'ebayenterprise_paypal/api_status_handler';

    /** @var EbayEnterprise_PayPal_Model_Config */
    protected $_configModel;

    /**
     * setup the config model
     */
    public function __construct()
    {
        $this->_configModel = Mage::getSingleton('ebayenterprise_paypal/config');
    }

    /**
     * @see EbayEnterprise_Eb2cCore_Helper_Interface::getConfigModel
     * Get payment config instantiated object.
     *
     * @param mixed $store
     *
     * @return EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel($this->_configModel);
    }

    /**
     * Return checkout quote object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    /**
     * Get the current store currency code.
     *
     * @see Mage_Core_Model_Store::getCurrentCurrencyCode
     * @return string
     * @codeCoverageIgnore
     */
    protected function _getCurrencyCode()
    {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }
}
