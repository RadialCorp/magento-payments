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
 * Class EbayEnterprise_PayPal_Model_Void
 *
 * Handle voiding PayPal auth
 */
class EbayEnterprise_PayPal_Model_Void
{
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    /**
     * Set up the logger
     */
    public function __construct()
    {
        $this->_logger = Mage::helper('ebayenterprise_magelog');
        $this->_context = Mage::helper('ebayenterprise_magelog/context');
    }

    /**
     * @param Mage_Sales_Model_Order
     * @return self
     */
    public function void(Mage_Sales_Model_Order $order)
    {
        if ($this->_canVoid($order)) {
            try {
                $this->_getVoidApi()->doVoidOrder($order);
            } catch (EbayEnterprise_PayPal_Exception $e) {
                $logMessage = 'Void request failed. See exception log for details.';
                $this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__));
                $this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
            }
        }
        return $this;
    }

    /**
     * Check if we can void the PayPal payment method in this order.
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    protected function _canVoid(Mage_Sales_Model_Order $order)
    {
        $payment = $order->getPayment();
        if (!$payment instanceof Mage_Sales_Model_Order_Payment) {
            return false;
        }
        $methodInstance = $payment->getMethodInstance();
        return (
            $methodInstance instanceof EbayEnterprise_Paypal_Model_Method_Express
            && $methodInstance->canVoid($payment)
        );
    }

    /**
     * @return EbayEnterprise_PayPal_Model_Express_Api
     */
    protected function _getVoidApi()
    {
        return Mage::getModel('ebayenterprise_paypal/express_api');
    }
}
