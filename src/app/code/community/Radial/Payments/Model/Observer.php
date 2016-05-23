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

class Radial_Payments_Model_Observer
{
    /**
     * The invoice is required for capture requests
     * but only the payment is given. This is a work-
     * around for capturing the correct invoice.
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleInvoiceCaptureEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $invoice = $event->getInvoice();
        $payment = $event->getPayment();
        $order = $payment->getOrder();
        $payment->setInvoiceForCapture($invoice);
        $methodInstance = $payment->getMethodInstance();
        if (is_callable([$methodInstance, 'confirm'])) {
            $methodInstance->setStore($order->getStoreId())
                ->confirm($payment, $invoice->getBaseGrandTotal());
        }
    }
}
