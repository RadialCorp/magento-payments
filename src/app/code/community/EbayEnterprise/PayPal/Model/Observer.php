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
 * Event observer for Ebay Enterprise PayPal
 */
class EbayEnterprise_Paypal_Model_Observer
{
    /** @var EbayEnterprise_PayPal_Model_Multishipping */
    protected $multiShipping;

    /**
     * @param array $initParams May have this key:
     *                          - 'multi_shipping' => EbayEnterprise_PayPal_Model_Multishipping
     */
    public function __construct(array $initParams=[])
    {
        list($this->multiShipping) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'multi_shipping', Mage::getModel('ebayenterprise_paypal/multishipping'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_PayPal_Model_Multishipping
     * @return array
     */
    protected function checkTypes(EbayEnterprise_PayPal_Model_Multishipping $multiShipping)
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     *
     * @param  array
     * @param  string $field Valid array key
     * @param  mixed
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * undo/cancel the PayPal payment
     *
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function rollbackExpressPayment(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order instanceof Mage_Sales_Model_Order) {
            $this->_getVoidModel()->void($order);
        }
        return $this;
    }

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
        Mage::getModel('ebayenterprise_paypal/order_create_payment')
            ->addPaymentsToPayload($order, $paymentContainer, $processedPayments);
        return $this;
    }

    /**
     * Update the order create request context with paypal information.
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handleOrderCreateContextEvent(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();
        $orderContext = $event->getOrderContext();
        Mage::getModel('ebayenterprise_paypal/order_create_context')
            ->updateOrderContext($order, $orderContext);
        return $this;
    }

    /**
     * @return EbayEnterprise_PayPal_Model_Void
     */
    protected function _getVoidModel()
    {
        return Mage::getModel('ebayenterprise_paypal/void');
    }

    /**
     * Listen to the 'controller_action_predispatch_checkout_multishipping_overview' event
     * in order to get the controller action and pass it down to the 'ebayenterprise_paypal/multishipping::initializePaypalExpressCheckout()'
     * method.
     *
     * @param  Varien_Event_Observer
     * @return self
     */
    public function handleControllerActionPredispatch(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event */
        $event = $observer->getEvent();
        /** @var Mage_Checkout_MultishippingController */
        $controllerAction = $event->getControllerAction();
        if ($controllerAction) {
            $this->multiShipping->initializePaypalExpressCheckout($controllerAction);
        }
        return $this;
    }

    /**
     * Listen to the 'ebayenterprise_multishipping_before_submit_order_create' event
     * in order to get the sales/quote and pass it down to the 'ebayenterprise_paypal/multishipping::processPaypalExpressPayment()'
     * method.
     *
     * @param  Varien_Event_Observer
     * @return self
     */
    public function handleEbayEnterpriseMultishippingBeforeSubmitOrderCreate(Varien_Event_Observer $observer)
    {
        /** @var Varien_Event */
        $event = $observer->getEvent();
        /** @var Mage_Sales_Model_Quote */
        $quote = $event->getQuote();
        if ($quote) {
            $this->multiShipping->processPaypalExpressPayment($quote);
        }
        return $this;
    }
}
