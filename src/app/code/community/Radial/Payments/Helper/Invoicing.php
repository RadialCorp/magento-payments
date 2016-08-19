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

class Radial_Payments_Helper_Invoicing extends Mage_Core_Helper_Abstract
{

    /** @var  Radial_Core_Model_Config_Registry */
    protected $config;

    public function __construct()
    {
        $this->config = Mage::getModel('radial_core/config_registry')
            ->setStore(null)
            ->addConfigModel(Mage::getSingleton('radial_payments/invoicing_config'));
    }

    /**
     * Call capture on the payment method without creating an invoice.
     * @param Mage_Sales_Model_Order
     * @param bool
     */
    public function confirmFundsForOrder(Mage_Sales_Model_Order $order)
    {
        $payment = $order->getPayment();
        $payment->getMethodInstance()
            ->capture($payment, $order->getBaseGrandTotal());
    }

    /**
     * Determine if order needs to be reconfirmed
     * @param Mage_Sales_Model_Order
     * @return bool
     */
    public function doesOrderNeedReconfirm(Mage_Sales_Model_Order $order)
    {
        $orderTime = strtotime($order->getCreatedAtDate());
        $elapsedTime = $this->getElapsedTimeInHours($orderTime);

        return $elapsedTime >= $this->config->reconfirmAge;
    }

    /**
     * Set order status: "Ready to Ship"
     * @param Mage_Sales_Model_Order
     * @return bool
     */
    public function setOrderReadyToShip(Mage_Sales_Model_Order $order)
    {
        $order->setState(
            Mage_Sales_Model_Order::STATE_PROCESSING,
            Radial_Payments_Model_Invoicing_Order::STATUS_READY_CODE
        )->save();
        return $this;
    }

    /**
     * Create an invoice from the shipment
     * @param Mage_Sales_Model_Order_Shipment
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function createInvoiceFromShipment(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $order = $shipment->getOrder();

	if( $order->getTotalDue() > 0 )
	{
		$orderItemArray = array();

                foreach( $order->getAllItems() as $orderItem )
                {
                        $orderItemArray[$orderItem->getId()] = 0;
                }

                foreach( $shipment->getAllItems() as $shipItem )
                {
                        $orderItemArray[$shipItem->getOrderItemId()] = $shipItem->getQty();
                }

		/** @var Mage_Sales_Model_Service_Order $orderService */
        	$orderService = Mage::getModel('sales/service_order', $order);
        	$invoice = $orderService->prepareInvoice($orderItemArray);
        
		$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::NOT_CAPTURE);
        	$invoice->register()->capture();

		$transactionSave = Mage::getModel('core/resource_transaction')
    				->addObject($invoice)
    				->addObject($invoice->getOrder());

		$transactionSave->save();

        	return $invoice;
	}

	return null;
    }

    /**
     * Process the invoice
     * @param Mage_Sales_Model_Order_Invoice
     * @return bool
     */
    public function processInvoice(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
        $payment->getMethodInstance()
            ->processInvoice($invoice, $payment);
        return $this;
    }

    /**
     * @param string
     * @return float
     */
    protected function getElapsedTimeInHours($from)
    {
        $to = $this->getDateModel()->timestamp();
        $elapsedTime = $to - $from;
        return $elapsedTime / 60 / 60;
    }

    /**
     * @return Mage_Core_Model_Date
     */
    protected function getDateModel()
    {
        return Mage::getModel('core/date');
    }

    /**
     * Check whether auto invoicing is enabled
     *
     * @return bool
     */
    public function isInvoicingEnabled()
    {
        if ($this->config->active) {
            return true;
        }
        return false;
    }
    
}
