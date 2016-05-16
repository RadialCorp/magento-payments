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

class EbayEnterprise_Paypal_Test_Model_ObserverTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function testRollbackExpressPayment()
    {
        $quote = $this->getModelMock('sales/quote');
        $order = $this->getModelMock('sales/order');
        $observerData = new Varien_Event_Observer(
            array('event' => new Varien_Event(
                array('quote' => $quote, 'order' => $order)
            ))
        );
        $voidModel = $this->getModelMock('ebayenterprise_paypal/void');
        $voidModel->expects($this->once())
            ->method('void')
            ->will($this->returnSelf());
        $this->replaceByMock('model', 'ebayenterprise_paypal/void', $voidModel);
        Mage::getModel('ebayenterprise_paypal/observer')
            ->rollbackExpressPayment($observerData);
    }

    /**
     * Scenario: Handle controller action pre-dispatch
     * Given a Varien_Event_Observer instance
     * When handling controller action pre-dispatch
     * Then get the controller action from the passed in varien event observer instance
     * And pass it down to the ebayenterprise_paypal/multishipping::initializePaypalExpressCheckout()
     * method.
     */
    public function testHandleControllerActionPredispatch()
    {
        /** @var Mock_Mage_Checkout_MultishippingController */
        $controllerAction = $this->getMock('Mage_Checkout_MultishippingController');
        /** @var Varien_Event_Observer */
        $observerData = new Varien_Event_Observer([
            'event' => new Varien_Event(['controller_action' => $controllerAction])
        ]);

        /** @var EbayEnterprise_PayPal_Model_Multishipping */
        $multiShipping = $this->getModelMock('ebayenterprise_paypal/multishipping', ['initializePaypalExpressCheckout']);
        $multiShipping->expects($this->once())
            ->method('initializePaypalExpressCheckout')
            ->with($this->identicalTo($controllerAction))
            ->will($this->returnSelf());

        /** @var EbayEnterprise_PayPal_Model_Multishipping */
        $observer = Mage::getModel('ebayenterprise_paypal/observer', ['multi_shipping' => $multiShipping]);
        $this->assertSame($observer, $observer->handleControllerActionPredispatch($observerData));
    }

    /**
     * Scenario: Handle EbayEnterprise Multi-shipping before submit order create event
     * Given a sales/quote instance
     * When handling EbayEnterprise Multi-shipping before submit order create event
     * Then get the sales/quote instance from the passed in varien event observer instance
     * And pass it down to the ebayenterprise_paypal/multishipping::processPaypalExpressPayment()
     * method.
     */
    public function testHandleEbayEnterpriseMultishippingBeforeSubmitOrderCreate()
    {
        /** @var Mage_Sales_Model_Quote */
        $quote = Mage::getModel('sales/quote');
        /** @var Varien_Event_Observer */
        $observerData = new Varien_Event_Observer([
            'event' => new Varien_Event(['quote' => $quote])
        ]);

        /** @var EbayEnterprise_PayPal_Model_Multishipping */
        $multiShipping = $this->getModelMock('ebayenterprise_paypal/multishipping', ['processPaypalExpressPayment']);
        $multiShipping->expects($this->once())
            ->method('processPaypalExpressPayment')
            ->with($this->identicalTo($quote))
            ->will($this->returnSelf());

        /** @var EbayEnterprise_PayPal_Model_Multishipping */
        $observer = Mage::getModel('ebayenterprise_paypal/observer', ['multi_shipping' => $multiShipping]);
        $this->assertSame($observer, $observer->handleEbayEnterpriseMultishippingBeforeSubmitOrderCreate($observerData));
    }
}
