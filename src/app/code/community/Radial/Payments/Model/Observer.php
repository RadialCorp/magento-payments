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
    const SETTLEMENT_FAILED = 'RADIAL_PAYMENT_SETTLEMENT_FAILED';
    /** @var  Radial_Payments_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /**
     * @param array $initParams Must include the payload key:
     *                          - 'helper' => Radial_Payments_Helper_Data
     *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $initParams = [])
    {
        list($this->helper, $this->logger, $this->context) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'helper', Mage::helper('radial_payments')),
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     * @param  Radial_Payments_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        Radial_Payments_Helper_Data $helper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     * @param  array $arr
     * @param  string|int $field Valid array key
     * @param  mixed $default
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }
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

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleInvoiceRegisterEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $invoice = $event->getInvoice();
        if ($this->canProcessInvoice($invoice)) {
            $this->processInvoice($invoice);
        }
    }

    /**
     * Do settlement debit for Radial payment methods
     * @param Mage_Sales_Model_Order_Invoice
     */
    protected function processInvoice(Mage_Sales_Model_Order_Invoice $invoice)
    {
        // The invoice must be saved before continuing.
        $invoice->save();
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if (method_exists($methodInstance, 'processInvoice')) {
            try {
                $methodInstance->setStore($order->getStoreId())
                    ->processInvoice($invoice, $invoice->getBaseGrandTotal());
            } catch (Exception $e) {
                // settlement must be allowed to fail
                // set invoice status as OPEN to trigger a  retry and notify admin
                $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);
                $errorMessage = $this->helper->__(self::SETTLEMENT_FAILED);
                $this->getSession()->addNotice($errorMessage);
                $this->logger->logException($e, $this->context->getMetaData(__CLASS__, [], $e));
            }
        }
    }

    /**
     * Settlement debit can be made when payment method is
     * allowed to capture, invoice is marked as PAID to confirm
     * funds, and the capture type is CAPTURE_ONLINE.
     * @param Mage_Sales_Model_Order_Invoice
     * @return bool
     */
    protected function canProcessInvoice(Mage_Sales_Model_Order_Invoice $invoice)
    {
        return $invoice->getOrder()->getPayment()->canCapture() &&
        $invoice->getState() == Mage_Sales_Model_Order_Invoice::STATE_PAID &&
        $invoice->getRequestedCaptureCase() == Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE;
    }

    /**
     * Retrieve adminhtml session model object
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}
