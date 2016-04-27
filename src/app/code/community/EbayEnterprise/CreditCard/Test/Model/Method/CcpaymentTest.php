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

class EbayEnterprise_CreditCard_Test_Model_Method_CcpaymentTest extends EbayEnterprise_Eb2cCore_Test_Base
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
        $helper = $this->getHelperMock('ebayenterprise_creditcard', array('getAvailableCardTypes'));
        $helper->expects($this->any())
            ->method('getAvailableCardTypes')
            ->will($this->returnValue($availableCardTypes));

        $method = Mage::getModel('ebayenterprise_creditcard/method_ccpayment', array('helper' => $helper));
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
        $this->setExpectedException('EbayEnterprise_CreditCard_Exception');

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

        $coreHelper = $this->getHelperMock('eb2ccore', array('getSdkApi'));
        $coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->will($this->returnValue($api));
        $ccHelper = $this->getHelperMock('ebayenterprise_creditcard', array('getTenderTypeForCcType'));
        $ccHelper->expects($this->any())
            ->method('getTenderTypeForCcType')
            ->will($this->returnValue('TT'));

        $payment = $this->getModelMockBuilder('ebayenterprise_creditcard/method_ccpayment')
            ->setMethods(array('_prepareApiRequest'))
            ->setConstructorArgs(array(array('core_helper' => $coreHelper, 'helper' => $ccHelper, 'checkout_session' => $this->_checkoutSession)))
            ->getMock();
        $payment->expects($this->any())
            ->method('_prepareApiRequest')
            ->will($this->returnSelf());

        $payment->authorize($payment, $amount);
    }
    /**
     * Network errors for payment
     */
    public function testAuthorizeApiNetworkError()
    {
        $this->_replaceCheckoutSession();
        $this->setExpectedException('EbayEnterprise_CreditCard_Exception');

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

        $coreHelper = $this->getHelperMock('eb2ccore', array('getSdkApi'));
        $coreHelper->expects($this->any())
            ->method('getSdkApi')
            ->will($this->returnValue($api));
        $ccHelper = $this->getHelperMock('ebayenterprise_creditcard', array('getTenderTypeForCcType'));
        $ccHelper->expects($this->any())
            ->method('getTenderTypeForCcType')
            ->will($this->returnValue('TT'));

        $payment = $this->getModelMockBuilder('ebayenterprise_creditcard/method_ccpayment')
            ->setMethods(array('_prepareApiRequest'))
            ->setConstructorArgs(array(array('core_helper' => $coreHelper, 'helper' => $ccHelper, 'checkout_session' => $this->_checkoutSession)))
            ->getMock();
        $payment->expects($this->any())
            ->method('_prepareApiRequest')
            ->will($this->returnSelf());

        $payment->authorize($payment, $amount);
    }
    /**
     * Build a mock credit card auth reply payload scripted to return the given
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
            array(false, false, false, true, 'EbayEnterprise_CreditCard_Exception'),
            // CVV failure
            array(false, false, true, false, 'EbayEnterprise_CreditCard_Exception'),
            // no success, no failures but still unacceptable
            array(false, false, true, true, 'EbayEnterprise_CreditCard_Exception'),
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
        $paymentMethod = Mage::getModel('ebayenterprise_creditcard/method_ccpayment');
        $this->assertSame(
            $paymentMethod,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($paymentMethod, '_validateResponse', array($payload))
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
        $helper = $this->getHelperMock('ebayenterprise_creditcard', array('getAvailableCardTypes', 'getConfigModel'));
        $helper->expects($this->any())
            ->method('getAvailableCardTypes')
            ->will($this->returnValue(array('VI' => 'Visa')));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(array('useClientSideEncryptionFlag' => true))));

        $ccMethod = $this->getModelMock(
            'ebayenterprise_creditcard/method_ccpayment',
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
            $this->setExpectedException('EbayEnterprise_CreditCard_Exception');
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
        $helper = $this->getHelperMock('ebayenterprise_creditcard', array('getConfigModel'));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));
        $lastFour = '1111';
        $info = Mage::getModel('payment/info');
        $data = array('cc_last4' => $lastFour);
        $method = Mage::getModel('ebayenterprise_creditcard/method_ccpayment', array('helper' => $helper));
        $method->setInfoInstance($info);
        $method->assignData($data);
        $this->assertSame($lastFour, $info->getCcLast4());
    }

    /**
     * verify the order address object for the quote address
     * with the highest dollar amount
     */
    public function testGetPrimaryShippingAddress()
    {
        $address = $this->getModelMockBuilder('sales/order_address')
            ->setMethods(null)
            ->getMock();
        // no need for any dependencies setup $highAmountAddress,by the constructor
        $ccPayment = $this->getModelMockBuilder('ebayenterprise_creditcard/method_ccpayment')
            ->disableOriginalConstructor()
            ->setMethods(['getQuotePrimaryShippingAddressId'])
            ->getMock();
        $order = $this->getModelMockBuilder('sales/order')
            ->disableOriginalConstructor()
            ->setMethods(['getAddressesCollection', 'getQuote'])
            ->getMock();
        $quote = $this->getModelMockBuilder('sales/quote')
            ->disableOriginalConstructor()
            ->getMock();
        $collection = $this->getModelMockBuilder('sales/order_address_collection')
            ->disableOriginalConstructor()
            ->setMethods(['getItemByColumnValue'])
            ->getMock();

        // get the address collection from the order and get the shipping addresses
        $order->expects($this->once())
            ->method('getAddressesCollection')
            ->will($this->returnValue($collection));
        $order->expects($this->once())
            ->method('getQuote')
            ->will($this->returnValue($quote));
        $ccPayment->expects($this->once())
            ->method('getQuotePrimaryShippingAddressId')
            ->with($this->identicalTo($quote))
            ->will($this->returnValue(2));
        $collection->expects($this->once())
            ->method('getItemByColumnValue')
            ->with($this->identicalTo('quote_address_id'), $this->identicalTo(2))
            ->will($this->returnValue($address));
        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($ccPayment, 'getPrimaryShippingAddress', [$order]);
        $this->assertSame($address, $result);
    }

    /**
     * verify the quote address id for the address with the highest amount
     * will be returned
     */
    public function testGetQuotePrimaryShippingAddressId()
    {
        $ccPayment = $this->getModelMockBuilder('ebayenterprise_creditcard/method_ccpayment')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $highAmountAddress = $this->getModelMockBuilder('sales/quote_address')
            ->setMethods(['getAddressId', 'getBaseSubtotal'])
            ->getMock();
        $address = $this->getModelMockBuilder('sales/quote_address')
            ->setMethods(['getAddressId', 'getBaseSubtotal'])
            ->getMock();
        $quote = $this->getModelMockBuilder('sales/quote')
            ->disableOriginalConstructor()
            ->setMethods(['getAllShippingAddresses'])
            ->getMock();

        $addresses = [$highAmountAddress, $address];
        $quote->expects($this->once())
            ->method('getAllShippingAddresses')
            ->will($this->returnValue($addresses));
        $highAmountAddress->expects($this->once())
            ->method('getAddressId')
            ->will($this->returnValue(10));
        $highAmountAddress->expects($this->atLeastOnce())
            ->method('getBaseSubtotal')
            ->will($this->returnValue(100.00));
        $address->expects($this->any())
            ->method('getAddressId')
            ->will($this->returnValue(5));
        $address->expects($this->atLeastOnce())
            ->method('getBaseSubtotal')
            ->will($this->returnValue(50.00));

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $ccPayment,
            'getQuotePrimaryShippingAddressId',
            [$quote]
        );
        $this->assertSame(10, $result);
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

        /** @var EbayEnterprise_CreditCard_Model_Method_Ccpayment $payment */
        $payment = Mage::getModel('ebayenterprise_creditcard/method_ccpayment');

        $this->assertSame($payment, EcomDev_Utils_Reflection::invokeRestrictedMethod($payment, '_prepareApiRequest', [$api, $orderPayment]));
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
        $creditCardException = 'EbayEnterprise_CreditCard_Exception';
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

        /** @var EbayEnterprise_CreditCard_Model_Method_Ccpayment $payment */
        $payment = Mage::getModel('ebayenterprise_creditcard/method_ccpayment');

        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $payment,
            '_sendAuthRequest',
            [$api]
        );
    }
}
