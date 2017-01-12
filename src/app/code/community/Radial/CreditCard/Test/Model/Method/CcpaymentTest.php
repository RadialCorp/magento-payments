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

use eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError;
use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;

class Radial_CreditCard_Test_Model_Method_CcpaymentTest extends Radial_Core_Test_Base
{
    /** @var Mage_Checkout_Model_Session $checkoutSession (stub) */
    protected $_checkoutSession;

    public function setUp()
    {
        $this->_checkoutSession = $this->getModelMockBuilder('checkout/session')
            ->disableOriginalConstructor()
            ->getMock();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);
    }
    /**
     * Needs to be called in ever test method to prevent "headers already sent
     * errors." Can't be in setUp as replaceByMock in setUp doesn't get cleaned
     * up properly after the test is complete.
     * @return self
     */
    protected function _replaceCheckoutSession()
    {
        $this->replaceByMock('singleton', 'checkout/session', $this->_checkoutSession);
        return $this;
    }
    /**
     * Get an address object with the given data
     * @param  array  $addrData
     * @return Mage_Sales_Model_Order_Address
     */
    protected function _getOrderAddress(array $addrData = array())
    {
        return Mage::getModel('sales/order_address', $addrData);
    }
    /**
     * Test the override of getConfigData for getting cctypes. Should return only
     * the available credit card types but in same format as expected if it were
     * coming directly from the config.
     */
    public function testGetConfigDataOverride()
    {
        $availableCardTypes = array('AE' => 'American Express', 'VI' => 'Visa', 'MC' => 'Master Card');
        $helper = $this->getHelperMock('radial_creditcard', array('getAvailableCardTypes'));
        $helper->expects($this->any())
            ->method('getAvailableCardTypes')
            ->will($this->returnValue($availableCardTypes));

        $method = Mage::getModel('radial_creditcard/method_ccpayment', array('helper' => $helper));
        $this->assertSame(
            'AE,VI,MC',
            $method->getConfigData('cctypes', null)
        );
    }
    /**
     * Test when an invalid payload is provided.
     */
    public function testAuthorizeApiInvalidPayload()
    {
        $this->_replaceCheckoutSession();
        // invalid payload should throw this exception to redirect back to payment
        // step to recollect/correct payment info
        $this->setExpectedException('Radial_CreditCard_Exception');

        $payment = Mage::getModel('sales/order_payment');
        $amount = 25.50;

        $request = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthRequest');
        $response = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthReply');
        $api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
        $api->expects($this->any())
            ->method('send')
            ->will($this->throwException(new InvalidPayload));
        $api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($request));
        $api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($response));

        $coreHelper = $this->getHelperMock('radial_core', array('getSdkApi'));
        $coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->will($this->returnValue($api));
        $ccHelper = $this->getHelperMock('radial_creditcard', array('getTenderTypeForCcType'));
        $ccHelper->expects($this->any())
            ->method('getTenderTypeForCcType')
            ->will($this->returnValue('TT'));

        $payment = $this->getModelMockBuilder('radial_creditcard/method_ccpayment')
            ->setMethods(array('_prepareAuthRequest'))
            ->setConstructorArgs(array(array('core_helper' => $coreHelper, 'helper' => $ccHelper, 'checkout_session' => $this->_checkoutSession)))
            ->getMock();
        $payment->expects($this->any())
            ->method('_prepareAuthRequest')
            ->will($this->returnSelf());

        $payment->authorize($payment, $amount);
    }
    /**
     * Network errors for payment
     */
    public function testAuthorizeApiNetworkError()
    {
        $this->_replaceCheckoutSession();
        $this->setExpectedException('Radial_CreditCard_Exception');

        $payment = Mage::getModel('sales/order_payment');
        $amount = 25.50;

        $request = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthRequest');
        $response = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthReply');
        $api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
        $api->expects($this->any())
            ->method('send')
            ->will($this->throwException(new NetworkError));
        $api->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($request));
        $api->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($response));

        $coreHelper = $this->getHelperMock('radial_core', array('getSdkApi'));
        $coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->will($this->returnValue($api));
        $ccHelper = $this->getHelperMock('radial_creditcard', array('getTenderTypeForCcType'));
        $ccHelper->expects($this->any())
            ->method('getTenderTypeForCcType')
            ->will($this->returnValue('TT'));

        $payment = $this->getModelMockBuilder('radial_creditcard/method_ccpayment')
            ->setMethods(array('_prepareAuthRequest'))
            ->setConstructorArgs(array(array('core_helper' => $coreHelper, 'helper' => $ccHelper, 'checkout_session' => $this->_checkoutSession)))
            ->getMock();
        $payment->expects($this->any())
            ->method('_prepareAuthRequest')
            ->will($this->returnSelf());

        $payment->authorize($payment, $amount);
    }
    /**
     * Build a mock credit card auth reply payload scripted to return the given
     * values for various success checks.
     * @param  enum (SUCCESS, FAIL, TIMEOUT)  $isFundsAvailable
     * @param  bool $isReauthorizationAttempted
     * @return Payload\Payment\IConfirmFundsReply
     */
    protected function _buildPayloadToValidateConfirmFunds($isFundsAvailable = "Success", $isReauthorizationAttempted = true)
    {
        $payload = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IConfirmFundsReply');
        $payload->expects($this->any())
            ->method('getFundsAvailable')
            ->will($this->returnValue($isFundsAvailable));
        $payload->expects($this->any())
            ->method('getReauthorizationAttempted')
            ->will($this->returnValue($isReauthorizationAttempted));
        return $payload;
    }

    /**
     * Build a mock confirm funds reply payload scripted to return the given
     * values for various success checks.
     * @param  bool $isSuccess
     * @param  bool $isAcceptable
     * @param  bool $isAvsSuccess
     * @param  bool $isCvvSuccess
     * @return Payload\Payment\ICreditCardAuthReply
     */
    protected function _buildPayloadToValidate($isSuccess = true, $isAcceptable = true, $isAvsSuccess = true, $isCvvSuccess = true)
    {
        $payload = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthReply');
        $payload->expects($this->any())
            ->method('getIsAuthSuccessful')
            ->will($this->returnValue($isSuccess));
        $payload->expects($this->any())
            ->method('getIsAuthAcceptable')
            ->will($this->returnValue($isAcceptable));
        $payload->expects($this->any())
            ->method('getIsAVSSuccessful')
            ->will($this->returnValue($isAvsSuccess));
        $payload->expects($this->any())
            ->method('getIsCVV2Successful')
            ->will($this->returnValue($isCvvSuccess));
        return $payload;
    }

    /**
     * Provide a payload to validate and the name of the exception that should
     * be thrown if the payload is invalid.
     * @return array
     */
    public function provideTestValidateResponse()
    {
        return array(
            // all pass
            array(true, true, true, true, null),
            // no success but acceptable, no AVS or CVV failures
            array(false, true, true, true, null),
            // AVS failure
            array(false, false, false, true, 'Radial_CreditCard_Exception'),
            // CVV failure
            array(false, false, true, false, 'Radial_CreditCard_Exception'),
            // no success, no failures but still unacceptable
            array(false, false, true, true, 'Radial_CreditCard_Exception'),
        );
    }

    /**
     * Provide a payload to validate and the name of the exception that should
     * be thrown if the payload is invalid.
     * @return array
     */
    public function provideTestValidateResponseConfirmFunds()
    {
        return array(
            // all pass
            array("SUCCESS", true),
            array("SUCCESS", false),
            array("TIMEOUT", true),
            array("TIMEOUT", false), 
            // failures
	    array("FAIL", true),
	    array("FAIL", false),
	);
    }


    /**
     * Response payload should pass or throw the expected exception
     *
     * @param  bool $isSuccess
     * @param  bool $isAcceptable
     * @param  bool $isAvsSuccess
     * @param  bool $isCvvSuccess
     * @param  string|null $exception Name of exception to throw, null if no expected exception
     * @dataProvider provideTestValidateResponse
     */
    public function testValidateResponse($isSuccess, $isAcceptable, $isAvsSuccess, $isCvvSuccess, $exception)
    {
        $payload = $this->_buildPayloadToValidate($isSuccess, $isAcceptable, $isAvsSuccess, $isCvvSuccess);
        $this->_replaceCheckoutSession();
        if ($exception) {
            $this->setExpectedException($exception);
        }
        $paymentMethod = Mage::getModel('radial_creditcard/method_ccpayment');
        $this->assertSame(
            $paymentMethod,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($paymentMethod, '_validateAuthResponse', array($payload))
        );
    }

    /**
     * Response payload should pass or throw the expected exception
     *
     * @param  enum (SUCCESS, FAIL, TIMEOUT)  $isFundsAvailable
     * @param  bool $isReauthorizationAttempted
     * @param  string|null $exception Name of exception to throw, null if no expected exception
     * @dataProvider provideTestValidateResponseConfirmFunds
     */
    public function testValidateResponseConfirmFunds($isFundsAvailable, $isReauthorizationAttempted, $exception)
    {
        $payload = $this->_buildPayloadToValidateConfirmFunds($isFundsAvailable, $isReauthorizationAttempted);
        if ($exception) {
            $this->setExpectedException($exception);
        }
        $paymentMethod = Mage::getModel('radial_creditcard/method_ccpayment');
        $this->assertSame(
            $paymentMethod,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($paymentMethod, '_validateConfirmFundsResponse', array($payload))
        );
    }

    /**
     * Validate card data when CSE is enabled. When disabled, uses parent validation
     * which is provided by Magento.
     * @param  string $infoModel      Model alias for the payment info model
     * @param  string $billingCountry
     * @param  string $expYear
     * @param  string $expMonth
     * @param  string $cardType
     * @param  bool   $isValid
     * @dataProvider dataProvider
     */
    public function testValidateWithEncryptedCardData($infoModel, $billingCountry, $expYear, $expMonth, $cardType, $isValid)
    {
        $this->_replaceCheckoutSession();

        $quoteBillingAddress = Mage::getModel('sales/quote_address', array('country_id' => $billingCountry));
        $orderBillingAddress = Mage::getModel('sales/order_address', array('country_id' => $billingCountry));
        $quote = Mage::getModel('sales/quote');
        $quote->setBillingAddress($quoteBillingAddress);
        $order = Mage::getModel('sales/order');
        $order->setBillingAddress($orderBillingAddress);
        $info = Mage::getModel($infoModel)
            // use setters instead of contstructor data as some of these setters have
            // actual implementation in some of the payment info models
            ->setQuote($quote)
            ->setOrder($order)
            ->setCcType($cardType)
            ->setCcExpYear($expYear)
            ->setCcExpMonth($expMonth);

        // stub the helper to return a known set of available card types and a
        // config model with CSE enabled
        $helper = $this->getHelperMock('radial_creditcard', array('getAvailableCardTypes', 'getConfigModel'));
        $helper->expects($this->any())
            ->method('getAvailableCardTypes')
            ->will($this->returnValue(array('VI' => 'Visa')));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array('useClientSideEncryptionFlag' => true))));

        $ccMethod = $this->getModelMock(
            'radial_creditcard/method_ccpayment',
            array('canUseForCountry'),
            false,
            array(array('helper' => $helper))
        );
        $ccMethod->setInfoInstance($info);
        // mock canUseForCountry call to prevent config dependency
        $ccMethod->expects($this->any())
            ->method('canUseForCountry')
            ->will($this->returnValueMap(array(
                array('US', true),
            )));

        if (!$isValid) {
            $this->setExpectedException('Radial_CreditCard_Exception');
        }
        $this->assertSame($ccMethod, $ccMethod->validate());
    }
    /**
     * Test assigning data to the payment info object. Ensure correct cc last 4
     * is assigned as this is not typical data being submitted.
     */
    public function testAssignData()
    {
        $config = $this->buildCoreConfigRegistry(array('useClientSideEncryptionFlag' => true));
        $helper = $this->getHelperMock('radial_creditcard', array('getConfigModel'));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));
        $lastFour = '1111';
        $info = Mage::getModel('payment/info');
        $data = array('cc_last4' => $lastFour);
        $method = Mage::getModel('radial_creditcard/method_ccpayment', array('helper' => $helper));
        $method->setInfoInstance($info);
        $method->assignData($data);
        $this->assertSame($lastFour, $info->getCcLast4());
    }

    /**
     * Scenario: Prepare API Request for virtual order
     * Given A virtual order. And A billing address.
     * When An API request is prepared for the order.
     * Then The API request body is set.
     * And The API request body uses the billing address for the shipping address.
     */
    public function testPrepareApiRequest()
    {
        /** @var array $billingData */
        $billingData = [
            'firstname' => 'Someone',
            'lastname' => 'Somebody',
            'telephone' => '555-555-5555',
            'street' => '630 Allendale Rd',
            'city' => 'King of Prussia',
            'region_code' => 'PA',
            'country' => 'US',
            'postcode' => '19604',
        ];
        /** @var Mage_Sales_Model_Order_Address $shippingAddress */
        $shippingAddress = Mage::getModel('sales/order_address', ['id' => 1]);
        /** @var Mage_Sales_Model_Order_Address $billingAddress */
        $billingAddress = Mage::getModel('sales/order_address', array_merge($billingData, ['id' => 2]));
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order', [
            'is_virtual' => true
        ]);
        $order->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress);
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = Mage::getModel('sales/order_payment', ['cc_exp_year' => 2023, 'cc_exp_month' => 8])->setOrder($order);

        $mockMethods = [
            'setIsEncrypted' => null,
            'setRequestId' => null,
            'setOrderId' => null,
            'setPanIsToken' => null,
            'setCardNumber' => null,
            'setExpirationDate' => null,
            'setCardSecurityCode' => null,
            'setAmount' => null,
            'setCurrencyCode' => null,
            'setEmail' => null,
            'setIp' => null,
            'setBillingFirstName' => $billingData['firstname'],
            'setBillingLastName' => $billingData['lastname'],
            'setBillingPhone' => $billingData['telephone'],
            'setBillingLines' => $billingData['street'],
            'setBillingCity' => $billingData['city'],
            'setBillingMainDivision' => $billingData['region_code'],
            'setBillingCountryCode' => $billingData['country'],
            'setBillingPostalCode' => $billingData['postcode'],
            // Expecting shipping setter methods for the request payload to be
            // fill-out with billing data.
            'setShipToFirstName' => $billingData['firstname'],
            'setShipToLastName' => $billingData['lastname'],
            'setShipToPhone' => $billingData['telephone'],
            'setShipToLines' => $billingData['street'],
            'setShipToCity' => $billingData['city'],
            'setShipToMainDivision' => $billingData['region_code'],
            'setShipToCountryCode' => $billingData['country'],
            'setShipToPostalCode' => $billingData['postcode'],
	    'setSchemaVersion' => null,
        ];
        /** @var ICreditCardAuthRequest $request **/
        $request = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ICreditCardAuthRequest', [], '', true, true, true, array_keys($mockMethods));
        foreach ($mockMethods as $method => $with) {
            if (is_null($with)) {
                $request->expects($this->once())
                    ->method($method)
                    ->will($this->returnSelf());
            } else {
                // Using "with" only when there's an actual value
                $request->expects($this->once())
                    ->method($method)
                    ->with($this->identicalTo($with))
                    ->will($this->returnSelf());
            }
        }
        /** @var IBidirectionalApi $api */
        $api = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi', [], '', true, true, true, ['getRequestBody']);
        $api->expects($this->once())
            ->method('getRequestBody')
            ->will($this->returnValue($request));

        /** @var Radial_CreditCard_Model_Method_Ccpayment $payment */
        $payment = Mage::getModel('radial_creditcard/method_ccpayment');

        $this->assertSame($payment, EcomDev_Utils_Reflection::invokeRestrictedMethod($payment, '_prepareAuthRequest', [$api, $orderPayment]));
    }

   /**
    * Scenario: Prepare Confirm Funds API Request
    * Given an order that has been authorized
    * When Confirm Funds is invoked by invoicing directly or fraud
    * Then the Confirm Funds Request API is set with the tender authorized for the order
    */
    public function testPrepareConfirmFundsApiRequest()
    {
	/** @var array $billingData */
        $billingData = [
            'firstname' => 'Someone',
            'lastname' => 'Somebody',
            'telephone' => '555-555-5555',
            'street' => '630 Allendale Rd',
            'city' => 'King of Prussia',
            'region_code' => 'PA',
            'country' => 'US',
            'postcode' => '19604',
        ];
        /** @var Mage_Sales_Model_Order_Address $shippingAddress */
        $shippingAddress = Mage::getModel('sales/order_address', ['id' => 1]);
        /** @var Mage_Sales_Model_Order_Address $billingAddress */
        $billingAddress = Mage::getModel('sales/order_address', array_merge($billingData, ['id' => 2]));
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order', [
            'is_virtual' => true
        ]);
        $order->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress);
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = Mage::getModel('sales/order_payment', ['cc_exp_year' => 2023, 'cc_exp_month' => 8, 'amount_authorized' => 50])->setOrder($order);

	$mockMethods = [
		'setRequestId' => null,
		'setOrderId' => null,
		'setPanIsToken' => null,
		'setCardNumber' => null,
		'setAmount' => null,
		'setCurrencyCode' => null,
		'setPerformReauthorization' => null,
	];

	/** @var IConfirmFundsRequest $request **/
        $request = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IConfirmFundsRequest', [], '', true, true, true, array_keys($mockMethods));

	 foreach ($mockMethods as $method => $with) {
            if (is_null($with)) {
                $request->expects($this->once())
                    ->method($method)
                    ->will($this->returnSelf());
            } else {
                // Using "with" only when there's an actual value
                $request->expects($this->once())
                    ->method($method)
                    ->with($this->identicalTo($with))
                    ->will($this->returnSelf());
            }
        }

	/** @var IBidirectionalApi $api */
        $api = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi', [], '', true, true, true, ['getRequestBody']);
        $api->expects($this->once())
            ->method('getRequestBody')
            ->will($this->returnValue($request));

        /** @var Radial_CreditCard_Model_Method_Ccpayment $payment */
        $payment = Mage::getModel('radial_creditcard/method_ccpayment');

        $this->assertSame($payment, EcomDev_Utils_Reflection::invokeRestrictedMethod($payment, '_prepareConfirmFundsRequest', [$api, $orderPayment, $orderPayment->getAmountAuthorized()]));
    }

   /**
    * Scenario: Prepare A Settlement API Debit Request
    * Given an order that has been authorized
    * When Settlement is Invoked by Invoice / Shipment 
    * Then the Settlement Request API is set with the tender authorized for the order
    */
    public function testPrepareSettlementApiRequest()
    {
        /** @var array $billingData */
        $billingData = [
            'firstname' => 'Someone',
            'lastname' => 'Somebody',
            'telephone' => '555-555-5555',
            'street' => '630 Allendale Rd',
            'city' => 'King of Prussia',
            'region_code' => 'PA',
            'country' => 'US',
            'postcode' => '19604',
        ];
        /** @var Mage_Sales_Model_Order_Address $shippingAddress */
        $shippingAddress = Mage::getModel('sales/order_address', ['id' => 1]);
        /** @var Mage_Sales_Model_Order_Address $billingAddress */
        $billingAddress = Mage::getModel('sales/order_address', array_merge($billingData, ['id' => 2]));
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order', [
            'is_virtual' => true
        ]);
        $order->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress);
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
	$orderPayment = Mage::getModel('sales/order_payment', ['cc_exp_year' => 2023, 'cc_exp_month' => 8, 'amount_authorized' => 50])->setOrder($order);

	//START Handle Invoice
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

        $mockMethods = [
                'setRequestId' => null,
                'setOrderId' => null,
                'setPanIsToken' => null,
                'setCardNumber' => null,
		'setInvoiceId' => null,
                'setAmount' => null,
                'setCurrencyCode' => null,
		'setTaxAmount' => null,
        	'setSettlementType' => null,
		'setClientContext' => null,
		'setFinalDebit' => null,
	];

        /** @var IConfirmFundsRequest $request **/
        $request = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPaymentSettlementRequest', [], '', true, true, true, array_keys($mockMethods));

         foreach ($mockMethods as $method => $with) {
            if (is_null($with)) {
                $request->expects($this->once())
                    ->method($method)
                    ->will($this->returnSelf());
            } else {
                // Using "with" only when there's an actual value
                $request->expects($this->once())
                    ->method($method)
                    ->with($this->identicalTo($with))
                    ->will($this->returnSelf());
            }
        }

        /** @var IBidirectionalApi $api */
        $api = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi', [], '', true, true, true, ['getRequestBody']);
        $api->expects($this->once())
            ->method('getRequestBody')
            ->will($this->returnValue($request));

        /** @var Radial_CreditCard_Model_Method_Ccpayment $payment */
        $payment = Mage::getModel('radial_creditcard/method_ccpayment');

        $this->assertSame($payment, EcomDev_Utils_Reflection::invokeRestrictedMethod($payment, '_handleDebitResponse', [$api, $invoice]));
    }

   /**
    * Scenario: Prepare Settlement API Credit Request
    * Given an order that has been authorized and invoices (shipped)
    * When a credit memo is placed 
    * Then the Settlement Request API is set to refund the portion of the order previously shipped
    */
    public function testPrepareSettlementApiRequestCredit()
    {
        /** @var array $billingData */
        $billingData = [
            'firstname' => 'Someone',
            'lastname' => 'Somebody',
            'telephone' => '555-555-5555',
            'street' => '630 Allendale Rd',
            'city' => 'King of Prussia',
            'region_code' => 'PA',
            'country' => 'US',
            'postcode' => '19604',
        ];
        /** @var Mage_Sales_Model_Order_Address $shippingAddress */
        $shippingAddress = Mage::getModel('sales/order_address', ['id' => 1]);
        /** @var Mage_Sales_Model_Order_Address $billingAddress */
        $billingAddress = Mage::getModel('sales/order_address', array_merge($billingData, ['id' => 2]));
        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order', [
            'is_virtual' => true
        ]);
        $order->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress);
        /** @var Mage_Sales_Model_Order_Payment $orderPayment */
        $orderPayment = Mage::getModel('sales/order_payment', ['cc_exp_year' => 2023, 'cc_exp_month' => 8, 'amount_authorized' => 50])->setOrder($order);

        //START Handle Creditmemo
        $creditmemo = Mage::getModel('sales/service_order', $order)->prepareCreditmemo();

        $mockMethods = [
                'setRequestId' => null,
                'setOrderId' => null,
                'setPanIsToken' => null,
                'setCardNumber' => null,
                'setInvoiceId' => null,
                'setAmount' => null,
                'setCurrencyCode' => null,
                'setTaxAmount' => null,
                'setSettlementType' => null,
                'setClientContext' => null,
                'setFinalDebit' => null,
        ];

        /** @var IConfirmFundsRequest $request **/
        $request = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPaymentSettlementRequest', [], '', true, true, true, array_keys($mockMethods));

         foreach ($mockMethods as $method => $with) {
            if (is_null($with)) {
                $request->expects($this->once())
                    ->method($method)
                    ->will($this->returnSelf());
            } else {
                // Using "with" only when there's an actual value
                $request->expects($this->once())
                    ->method($method)
                    ->with($this->identicalTo($with))
                    ->will($this->returnSelf());
            }
        }

        /** @var IBidirectionalApi $api */
        $api = $this->getMockForAbstractClass('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi', [], '', true, true, true, ['getRequestBody']);
        $api->expects($this->once())
            ->method('getRequestBody')
            ->will($this->returnValue($request));

        /** @var Radial_CreditCard_Model_Method_Ccpayment $payment */
        $payment = Mage::getModel('radial_creditcard/method_ccpayment');

        $this->assertSame($payment, EcomDev_Utils_Reflection::invokeRestrictedMethod($payment, '_prepareCreditRequest', [$api, $creditmemo, $orderPayment]));
    }

    /**
     * Provide exceptions that can be thrown from the SDK and the exception
     * expected to be thrown after handling the SDK exception.
     *
     * @return array
     */
    public function provideSdkExceptions()
    {
        $invalidPayload = '\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload';
        $networkError = '\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError';
        $unsupportedOperation = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation';
        $unsupportedHttpAction = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction';
        $baseException = 'Exception';
        $creditCardException = 'Radial_CreditCard_Exception';
        return [
            [$invalidPayload, $creditCardException],
            [$networkError, $creditCardException],
            [$unsupportedOperation, $unsupportedOperation],
            [$unsupportedHttpAction, $unsupportedHttpAction],
            [$baseException, $baseException],
        ];
    }

    /**
     * GIVEN An <api> that will thrown an <exception> of <exceptionType> when making a request.
     * WHEN A request is made.
     * THEN The <exception> will be caught.
     * AND An exception of <expectedExceptionType> will be thrown.
     *
     * @param string
     * @param string
     * @dataProvider provideSdkExceptions
     */
    public function testSdkExceptionHandling($exceptionType, $expectedExceptionType)
    {
        // Prevent session errors while handling some types of exceptions.
        $this->_replaceCheckoutSession();

        $exception = new $exceptionType(__METHOD__ . ': Test Exception');
        $api = $this->getMock('\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi');
        $api->method('send')
            ->will($this->throwException($exception));

        $this->setExpectedException($expectedExceptionType);

        /** @var Radial_CreditCard_Model_Method_Ccpayment $payment */
        $payment = Mage::getModel('radial_creditcard/method_ccpayment');

        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $payment,
            '_sendRequest',
            [$api]
        );
    }
}
