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
     * @param Varien_Event_Observer
     */
    public function handleInvoiceViewEvent(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_View) {
            if ($block->getInvoice()->getState() !== Mage_Sales_Model_Order_Invoice::STATE_CANCELED) {
                $confirmationMessage = Mage::helper('core')->jsQuoteEscape(
                    Mage::helper('sales')->__('Are you sure you want to retry settlement?')
                );
                $retrySettlementUrl = $block->getUrl('*/*/settlement', ['invoice_id' => $block->getInvoice()->getId()]);
                $block->addButton('settlement', [
                        'label'     => Mage::helper('sales')->__('Retry Settlement'),
                        'class'     => 'save',
                        'onclick'   => 'confirmSetLocation(\'' . $confirmationMessage . '\', \''.$retrySettlementUrl.'\')'
                    ]
                );
            }
        }
    }

    public function handlePaymentSettlementsStatusEvent(Varien_Event_Observer $observer)
    {
        Mage::getModel(
            'radial_payments/events_settlementStatus',
            ['payload' => $observer->getEvent()->getPayload()]
        )->process();
    }

    public function processOrderCancel(Varien_Event_Observer $observer)
    {
	$payment = $observer->getEvent()->getPayment();
	$order = $payment->getOrder();
	$qtyOrdered = 0;
	$qtyInvoiced = 0;
	$orderItemArray = array();
	foreach ($order->getAllItems() as $orderItem) 
	{
		$qtyOrdered += $orderItem->getQtyOrdered();
		$qtyInvoiced += $orderItem->getQtyInvoiced();
		$orderItemArray[$orderItem->getId()] = 0;
	}
	if( (int)$qtyOrdered - (int)$qtyInvoiced !== 0 )
	{
		//MPTF-143 create a 0.00 invoice and process it, if the order has been partially invoiced and canceled.
		/** @var Mage_Sales_Model_Service_Order $orderService */
                $orderService = Mage::getModel('sales/service_order', $order);
                $invoice = $orderService->prepareInvoice($orderItemArray);
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::NOT_CAPTURE);
		$invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID);
                $invoice->register();
                $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());
                $transactionSave->save();
		$payment->getMethodInstance()->processInvoice($invoice, $payment);
	}
	$payment->getMethodInstance()->cancel($payment);
    }

    public function transferRemainingGwPrices(Varien_Event_Observer $observer)
    {
    	$_invoice = $observer->getEvent()->getInvoice();
	$_order = $_invoice->getOrder();

	if ($_invoice->getUpdatedAt() == $_invoice->getCreatedAt()) 
	{
		foreach( $_invoice->getAllItems() as $invoiceItem )
		{
			$itemC = Mage::getModel('sales/order_item')->getCollection()
                                        ->addFieldToFilter('item_id', array('eq' => $invoiceItem->getOrderItemId()));

			if( $itemC->getSize() > 0 )
			{
				$item = $itemC->getFirstItem();
				$gwPrice = $item->getGwPrice();
				$gwTax = $item->getGwTaxAmount();
				 
				$invoiceQty = $invoiceItem->getQty();

				$gwItemsP += $gwPrice * $invoiceQty;
				$gwItemsTaxP += $gwTax * $invoiceQty;
			}
		}
		
		if( !$_invoice->getGwPrice() )
		{
			$prevSubtotal = $_invoice->getSubtotal();
			$prevGrandtotal = $_invoice->getGrandTotal();
			$prevSubtotalTax = $_invoice->getSubtotalInclTax();

			// Somehow GW Tax is Being Added In to Subtotal, so subtract.
			$_invoice->setData('subtotal_incl_tax', $prevSubtotalTax - $gwItemsTaxP);
			$_invoice->setData('base_subtotal_incl_tax', $prevSubtotalTax - $gwItemsTaxP);

			$_invoice->setData('gw_items_price', $gwItemsP);
                	$_invoice->setData('gw_items_base_price', $gwItemsP);
                	$_invoice->setData('gw_items_base_tax_amount', $gwItemsTaxP);
                	$_invoice->setData('gw_items_tax_amount', $gwItemsTaxP);
			$_invoice->setData('grand_total', $prevGrandtotal + $gwItemsP);
			$_invoice->setData('base_grand_total', $prevGrandtotal + $gwItemsP);

                	$_invoice->getResource()->saveAttribute($_invoice, 'gw_items_price');
                	$_invoice->getResource()->saveAttribute($_invoice, 'gw_items_base_price');
                	$_invoice->getResource()->saveAttribute($_invoice, 'gw_items_base_tax_amount');
                	$_invoice->getResource()->saveAttribute($_invoice, 'gw_items_tax_amount');
			$_invoice->getResource()->saveAttribute($_invoice, 'grand_total');
			$_invoice->getResource()->saveAttribute($_invoice, 'base_grand_total');
		} else {
			$prevGrandtotal = $_invoice->getGrandTotal();
			$_invoice->setData('base_grand_total', $prevGrandtotal);
			$_invoice->getResource()->saveAttribute($_invoice, 'base_grand_total');
		}
	}
    }

    public function transferRemainingGwPricesCreditMemo(Varien_Event_Observer $observer)
    {
        $_creditmemo = $observer->getEvent()->getCreditmemo();
        $_order = $_creditmemo->getOrder();

        if ($_creditmemo->getUpdatedAt() == $_creditmemo->getCreatedAt())
        {
        	foreach( $_creditmemo->getAllItems() as $creditmemoItem )
                {
                        $itemC = Mage::getModel('sales/order_item')->getCollection()
                                        ->addFieldToFilter('item_id', array('eq' => $creditmemoItem->getOrderItemId()));

                        if( $itemC->getSize() > 0 )
                        {
                                $item = $itemC->getFirstItem();
                                $gwPrice = $item->getGwPrice();
                                $gwTax = $item->getGwTaxAmount();

                                $creditmemoQty = $creditmemoItem->getQty();

                                $gwItemsP += $gwPrice * $creditmemoQty;
                                $gwItemsTaxP += $gwTax * $creditmemoQty;
                        }
                }

                if( !$_creditmemo->getGwPrice() )
                {
                        $prevSubtotal = $_creditmemo->getSubtotal();
                        $prevGrandtotal = $_creditmemo->getGrandTotal();
                        $prevSubtotalTax = $_creditmemo->getSubtotalInclTax();

                        // Somehow GW Tax is Being Added In to Subtotal, so subtract.
                        $_creditmemo->setData('subtotal_incl_tax', $prevSubtotalTax - $gwItemsTaxP);
                        $_creditmemo->setData('base_subtotal_incl_tax', $prevSubtotalTax - $gwItemsTaxP);

                        $_creditmemo->setData('gw_items_price', $gwItemsP);
                        $_creditmemo->setData('gw_items_base_price', $gwItemsP);
                        $_creditmemo->setData('gw_items_base_tax_amount', $gwItemsTaxP);
                        $_creditmemo->setData('gw_items_tax_amount', $gwItemsTaxP);
                        $_creditmemo->setData('grand_total', $prevGrandtotal + $gwItemsP);
                        $_creditmemo->setData('base_grand_total', $prevGrandtotal + $gwItemsP);

                        $_creditmemo->getResource()->saveAttribute($_creditmemo, 'gw_items_price');
                        $_creditmemo->getResource()->saveAttribute($_creditmemo, 'gw_items_base_price');
                        $_creditmemo->getResource()->saveAttribute($_creditmemo, 'gw_items_base_tax_amount');
                        $_creditmemo->getResource()->saveAttribute($_creditmemo, 'gw_items_tax_amount');
                        $_creditmemo->getResource()->saveAttribute($_creditmemo, 'grand_total');
                        $_creditmemo->getResource()->saveAttribute($_creditmemo, 'base_grand_total');
                } else {
                        $prevGrandtotal = $_creditmemo->getGrandTotal();
                        $_creditmemo->setData('base_grand_total', $prevGrandtotal);
                        $_creditmemo->getResource()->saveAttribute($_creditmemo, 'base_grand_total');
                }
	}
    }
}
