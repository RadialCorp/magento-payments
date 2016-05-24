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
     * Remove the capture button from un-captured invoices.
     * This overrides 'No Capture' invoices which allow the
     * admin to 'capture' an invoice after being created.
     * The desired order flow is to capture payment 'online'
     * when the invoice is created to confirm that funds are
     * available before making a settlement request.
     * @param Varien_Event_Observer $observer
     */
    public function handleInvoiceViewEvent(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_View) {
            if ($block->getInvoice()->canCapture()) {
                $block->removeButton('capture');
            }
        }
    }
}
