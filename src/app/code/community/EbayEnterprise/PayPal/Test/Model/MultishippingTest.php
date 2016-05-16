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

class EbayEnterprise_Paypal_Test_Model_MultishippingTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Scenario: Initialize PayPal Express checkout
     * Given an instance of Mage_Checkout_MultishippingController class
     * When initializing PayPal Express checkout
     * Then if is redirected to PayPal then redirect to the PayPal Express Start action
     * otherwise continue as normal.
     *
     * @param bool
     * @dataProvider provideTrueFalse
     */
    public function testInitializePaypalExpressCheckout($isRedirectToPayPal)
    {
        /** @var bool */
        $isWhen = $isRedirectToPayPal ? $this->atLeastOnce() : $this->never();
        /** @var bool */
        $isNotWhen = $isRedirectToPayPal ? $this->never() : $this->atLeastOnce();
        /** @var string */
        $url = 'http://some-mage-store.com/checkout/paypal/express/start';
        /** @var array */
        $paymentData = [];

        /** @var Mage_Core_Controller_Request_Http */
        $request = $this->getMock('Mage_Core_Controller_Request_Http', ['getParam', 'setPost']);
        $request->expects($this->once())
            ->method('getParam')
            ->with($this->identicalTo('payment'))
            ->will($this->returnValue($paymentData));
        $request->expects($isNotWhen)
            ->method('setPost')
            ->with($this->identicalTo('payment'), $this->identicalTo($paymentData))
            ->will($this->returnSelf());

        /** @var Mage_Core_Controller_Response_Http */
        $response = $this->getMock('Mage_Core_Controller_Response_Http', ['setRedirect']);
        $response->expects($isWhen)
            ->method('setRedirect')
            ->with($this->identicalTo($url))
            ->will($this->returnSelf());

        /** @var Mock_Mage_Checkout_MultishippingController */
        $controllerAction = $this->getMock('Mage_Checkout_MultishippingController', ['getRequest', 'getResponse']);
        $controllerAction->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $controllerAction->expects($isWhen)
            ->method('getResponse')
            ->will($this->returnValue($response));

        /** @var Mage_Sales_Model_Quote */
        $quote = $this->getModelMock('sales/quote', ['setIsMultiShipping']);
        $quote->expects($isNotWhen)
            ->method('setIsMultiShipping')
            ->with($this->identicalTo(true))
            ->will($this->returnSelf());

        /** @var Mage_Checkout_Model_Session */
        $checkoutSession = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->setMethods(['getQuote', 'getMultiShippingPaymentData', 'setIsUseMultiShippingCheckout', 'setMultiShippingPaymentData'])
            ->getMock();
        $checkoutSession->expects($isNotWhen)
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $checkoutSession->expects($this->once())
            ->method('getMultiShippingPaymentData')
            ->will($this->returnValue(null));
        $checkoutSession->expects($isWhen)
            ->method('setIsUseMultiShippingCheckout')
            ->with($this->identicalTo(true))
            ->will($this->returnSelf());
        $checkoutSession->expects($this->atLeastOnce())
            ->method('setMultiShippingPaymentData')
            ->with($this->identicalTo($isRedirectToPayPal ? $paymentData : null))
            ->will($this->returnSelf());

        /** @var EbayEnterprise_PayPal_Model_Multishipping */
        $multiShipping = $this->getModelMock('ebayenterprise_paypal/multishipping', ['getCheckoutSession', 'isRedirectToPayPal', 'getPayPalExpressStartupUrl']);
        $multiShipping->expects($this->once())
            ->method('getCheckoutSession')
            ->will($this->returnValue($checkoutSession));
        $multiShipping->expects($this->once())
            ->method('isRedirectToPayPal')
            ->with($this->identicalTo($paymentData))
            ->will($this->returnValue($isRedirectToPayPal));
        $multiShipping->expects($isWhen)
            ->method('getPayPalExpressStartupUrl')
            ->will($this->returnValue($url));
        $this->assertSame($multiShipping, $multiShipping->initializePaypalExpressCheckout($controllerAction));
    }

    /**
     * @return array
     */
    public function providerIsRedirectToPayPal()
    {
        return [
            [['method' => EbayEnterprise_PayPal_Model_Method_Express::CODE], true],
            [['method' => EbayEnterprise_PayPal_Model_Method_Express::CODE, 'is_return_from_paypal' => true], false],
            [[], false],
        ];
    }

    /**
     * Scenario: Determine if we are in a state to redirect to PayPal start controller action
     * Given an array of payment data
     * When determining if we are in a state to redirect to PayPal start controller action
     * Then return true when we are in a state of redirecting to PayPal start controller action
     * otherwise return false.
     *
     * @param array
     * @param bool
     * @dataProvider providerIsRedirectToPayPal
     */
    public function testIsRedirectToPayPal(array $paymentData, $result)
    {
        /** @var EbayEnterprise_PayPal_Model_Multishipping */
        $multiShipping = Mage::getModel('ebayenterprise_paypal/multishipping');
        $this->assertSame($result, EcomDev_Utils_Reflection::invokeRestrictedMethod($multiShipping, 'isRedirectToPayPal', [$paymentData]));
    }

    /**
     * Scenario: Process PayPal Express payment
     * Given a sales/quote instance
     * When processing PayPal Express payment
     * Then if the quote is multi-shipping and the payment method is PayPal, then,
     * make PayPal do express ROM Service called
     * And make PayPal do authorization ROM service called
     * And finally import the data into quote payment.
     */
    public function testProcessPaypalExpressPayment()
    {
        /** @var array */
        $doExpressData = ['paypal_express_checkout_address_status' => 'Confirmed'];
        /** @var array */
        $doAuthorizeData = ['is_authorized' => true];
        /** @var array */
        $paypalData = array_merge($doExpressData, $doAuthorizeData);
        /** @var bool */
        $isMultiShipping = true;
        /** @var string */
        $token = 'AD-00009929112112';
        /** @var string */
        $payerId = '93881LO112POMA34';
        /** @var array */
        $data = [
            'paypal_express_checkout_token' => $token,
            'paypal_express_checkout_payer_id' => $payerId,
        ];
        /** @var Mage_Sales_Model_Quote_Payment */
        $payment = $this->getModelMock('sales/quote_payment', ['getAdditionalInformation', 'getMethod', 'importData']);
        $payment->expects($this->once())
            ->method('getAdditionalInformation')
            ->will($this->returnValue($data));
        $payment->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue(EbayEnterprise_PayPal_Model_Method_Express::CODE));
        $payment->expects($this->once())
            ->method('importData')
            ->with($this->identicalTo($paypalData))
            ->will($this->returnSelf());

        /** @var Mage_Sales_Model_Quote */
        $quote = $this->getModelMock('sales/quote', ['getPayment', 'getIsMultiShipping', 'collectTotals']);
        $quote->expects($this->once())
            ->method('getPayment')
            ->will($this->returnValue($payment));
        $quote->expects($this->once())
            ->method('getIsMultiShipping')
            ->will($this->returnValue($isMultiShipping));
        $quote->expects($this->once())
            ->method('collectTotals')
            ->will($this->returnSelf());

        /** @var EbayEnterprise_Paypal_Model_Express_Api */
        $api = $this->getModelMock('ebayenterprise_paypal/express_api', ['doExpressCheckout', 'doAuthorization']);
        $api->expects($this->once())
            ->method('doExpressCheckout')
            ->with($this->identicalTo($quote), $this->identicalTo($token), $this->identicalTo($payerId))
            ->will($this->returnValue($doExpressData));
        $api->expects($this->once())
            ->method('doAuthorization')
            ->with($this->identicalTo($quote))
            ->will($this->returnValue($doAuthorizeData));

        /** @var EbayEnterprise_PayPal_Model_Multishipping */
        $multiShipping = Mage::getModel('ebayenterprise_paypal/multishipping', ['api' => $api]);
        $this->assertSame($multiShipping, $multiShipping->processPaypalExpressPayment($quote));
    }
}
