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
class Radial_PayPal_Helper_Data extends Mage_Core_Helper_Abstract implements Radial_Core_Helper_Interface
{
    const STATUS_HANDLER_PATH = 'radial_paypal/api_status_handler';

    /** @var Radial_PayPal_Model_Config */
    protected $_configModel;

    /**
     * setup the config model
     */
    public function __construct()
    {
        $this->_configModel = Mage::getSingleton('radial_paypal/config');
    }

    /**
     * @see Radial_Core_Helper_Interface::getConfigModel
     * Get payment config instantiated object.
     *
     * @param mixed $store
     *
     * @return Radial_Core_Model_Config_Registry
     */
    public function getConfigModel($store = null)
    {
        return Mage::getModel('radial_core/config_registry')
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

    /**
     * @param Varien_Object $payment
     * @param $amountToCapture
     * @return bool
     */
    public function isFinalDebit(Varien_Object $payment, $amountToCapture)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $amountToCapture = $this->_formatAmount($amountToCapture);
        $orderGrandTotal = $this->_formatAmount($order->getBaseGrandTotal());
        if ($orderGrandTotal == $this->_formatAmount($payment->getBaseAmountPaid()) + $amountToCapture) {
            if (false !== $payment->getShouldCloseParentTransaction()) {
                $payment->setShouldCloseParentTransaction(true);
            }
            return true;
        }
        return false;
    }
    /**
     * Round up and cast specified amount to float or string
     *
     * @param string|float
     * @return string|float
     */
    protected function _formatAmount($amount)
    {
        return Mage::app()->getStore()->roundPrice($amount);
    }
}
