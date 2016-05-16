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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderContext;

class EbayEnterprise_PayPal_Model_Order_Create_Context
{
    /**
     * Add PayPal context information to the payload
     * @param  Mage_Sales_Model_Order $order
     * @param  IOrderContext          $context
     * @return self
     */
    public function updateOrderContext(
        Mage_Sales_Model_Order $order,
        IOrderContext $context
    ) {
        $payment = $order->getPayment();
        if ($payment->getMethod() === Mage::getModel('ebayenterprise_paypal/method_express')->getCode()) {
            $additionalInfo = new Varien_Object($payment->getAdditionalInformation());
            $context
                ->setPayPalPayerId($additionalInfo->getPaypalExpressCheckoutPayerId())
                ->setPayPalPayerStatus($additionalInfo->getPaypalExpressCheckoutPayerStatus())
                ->setPayPalAddressStatus($additionalInfo->getPaypalExpressCheckoutAddressStatus());
        }
        return $this;
    }
}
