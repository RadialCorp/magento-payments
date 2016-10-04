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

class Radial_Payments_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Determine if this is the final settlement call
     * @param Mage_Sales_Model_Order
     * @return bool
     */
    public function isFinalDebit(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyOrdered() > $item->getQtyInvoiced()) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Failed confirm funds request must cancel remaining items
     * @param Mage_Sales_Model_Order
     * @param string
     * @throws Exception
     */
    public function failConfirmFundsRequest(Mage_Sales_Model_Order $order, $message = '')
    {
        $this->rollbackOrderItemDataChanges($order);
        $order->cancel();

	$message = "Ordered Canceled by ConfirmFunds";

	$order->addStatusHistoryComment($message);
	$order->save();

	return $this;
    }

    /**
     * Rollback data changes to order items that occurred during $invoice->register()
     * @param Mage_Sales_Model_Order
     */
    protected function rollbackOrderItemDataChanges(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            $origData = $item->getOrigData();
            $item->setData($origData);
        }
    }

    /**
     * Determine if an order can be canceled, do not cancel order if there
     * are item(s) left to possibly refund
     * @param Mage_Sales_Model_Order
     * @return bool
     */
    protected function canCancelOrder(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item){
            $qtyRemaining = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();
            if ($qtyRemaining > 0) {
                return false;
            }
        }
        return true;
    }
}
