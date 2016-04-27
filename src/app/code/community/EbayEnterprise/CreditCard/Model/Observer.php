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

class EbayEnterprise_CreditCard_Model_Observer
{
    /**
     * add paypal payment payloads to the order create
     * request.
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleOrderCreatePaymentEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $processedPayments = $event->getProcessedPayments();
        $paymentContainer = $event->getPaymentContainer();
        Mage::getModel('ebayenterprise_creditcard/order_create_payment')
            ->addPaymentsToPayload($order, $paymentContainer, $processedPayments);
        return $this;
    }
}
