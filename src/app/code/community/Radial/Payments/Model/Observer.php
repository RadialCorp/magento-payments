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
     * Remove the capture button to avoid capturing payment after
     * @param Varien_Event_Observer $observer
     */
    public function handleInvoiceViewEvent(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_View) {
            $block->removeButton('capture');
            // todo add settlement retry button
        }
    }

    /**
     * The invoice is required for settlement requests
     * but only the payment is given. This is a work-
     * around for capturing the correct invoice.
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleInvoiceSettlementEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $invoice = $event->getInvoice();
        if ($this->canMakeSettlement($invoice)) {
            $this->makeSettlement($invoice);
        } 
    }

    /**
     * Only Radial payment methods will have the settlement public method.
     * @param Mage_Sales_Model_Order_Invoice
     */
    protected function makeSettlement(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if (method_exists($methodInstance, 'settlement')) {
            $methodInstance->setStore($order->getStoreId())
                ->settlement($invoice, $invoice->getBaseGrandTotal());
        }
    }

    /**
     * 
     * @param Mage_Sales_Model_Order_Invoice
     * @return bool
     */
    protected function canMakeSettlement(Mage_Sales_Model_Order_Invoice $invoice)
    {
        return $invoice->canCapture() &&
        $invoice->getRequestedCaptureCase() == Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE;
    }
}
