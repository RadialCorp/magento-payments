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

use eBayEnterprise\RetailOrderManagement\Api;
use eBayEnterprise\RetailOrderManagement\Payload;
use eBayEnterprise\RetailOrderManagement\Payload\PayloadMap;
use eBayEnterprise\RetailOrderManagement\Payload\ValidatorIterator;
use Psr\Log\NullLogger;

class EbayEnterprise_PayPal_Test_Model_Express_ApiTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const BIDIRECTIONAL_API = '\eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi';
    const SETEXPRESS_REQUEST_PAYLOAD =
        '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalSetExpressCheckoutRequest';
    const SETEXPRESS_REPLY_PAYLOAD =
        '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalSetExpressCheckoutReply';
    const GETEXPRESS_REQUEST_PAYLOAD =
        '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalGetExpressCheckoutRequest';
    const GETEXPRESS_REPLY_PAYLOAD =
        '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalGetExpressCheckoutReply';
    const DOEXPRESS_REQUEST_PAYLOAD =
        '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoExpressCheckoutRequest';
    const DOEXPRESS_REPLY_PAYLOAD =
        '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoExpressCheckoutReply';
    const DOAUTH_REQUEST_PAYLOAD =
        '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoAuthorizationRequest';
    const DOAUTH_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoAuthorizationReply';
    const DOVOID_REQUEST_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoVoidRequest';
    const DOVOID_REPLY_PAYLOAD = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\IPayPalDoVoidReply';
    const LINE_ITEM_ITERABLE = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\LineItemIterable';
    const LINE_ITEM = '\eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItem';

    protected $sdk;
    protected $coreHelper;
    protected $helper;
    protected $checkoutSession;
    protected $getSdkApiMap;
    protected $cancelUrl = 'cancel url';
    protected $returnUrl = 'return url';
    protected $token = 'token';
    protected $orderId = 'order id';
    protected $currencyCode = 'USD';
    protected $lineItemStub;
    protected $lineItemIterableStub;
    protected $payerId = 'payer id';
    protected $pickUpStoreId = 'pick up stroe id';

    public function setUp()
    {
        parent::setUp();

        // suppressing the real session from starting
        $session = $this->getModelMockBuilder('core/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();
        $this->replaceByMock('singleton', 'core/session', $session);

        // disable _construct to prevent excessive stubs
        $this->coreUrl = $this->getModelMock(
            'core/url',
            ['_construct', 'getUrl']
        );
        $this->coreUrl->expects($this->any())
            ->method('getUrl')->will(
                $this->returnValueMap(
                    [
                        ['ebayenterprise_paypal_express/checkout/return', [], 'the.return.url'],
                        ['ebayenterprise_paypal_express/checkout/cancel', [], 'the.cancel.url'],
                    ]
                )
            );
        // stub sdk
        $this->sdk = $this->getMock(self::BIDIRECTIONAL_API);
        $this->sdk->expects($this->any())
            ->method('setRequestBody')
            ->will($this->returnSelf());
        $this->sdk->expects($this->any())
            ->method('send')->will($this->returnSelf());
        $this->getSdkApiMap = [
            ['payments', 'paypal/setExpress', [], null, $this->sdk],
            ['payments', 'paypal/getExpress', [], null, $this->sdk],
            ['payments', 'paypal/doExpress', [], null, $this->sdk],
            ['payments', 'paypal/doAuth', [], null, $this->sdk],
            ['payments', 'paypal/void', [], null, $this->sdk],
        ];
        $this->lineItemStub = $this->getMock(self::LINE_ITEM);
        $this->stubAcceptStrReturnSelf(
            ['setName', 'setCurrencyCode'],
            $this->lineItemStub
        );
        $this->lineItemStub->expects($this->any())
            ->method('setUnitAmount')
            ->with($this->isNumeric())
            ->will($this->returnSelf());
        // sequence number can be set to any scalar
        $this->lineItemStub->expects($this->any())
            ->method('setSequenceNumber')
            ->with($this->isScalar())
            ->will($this->returnSelf());
        $this->lineItemStub->expects($this->any())
            ->method('setQuantity')
            ->with($this->isType('int'))
            ->will($this->returnSelf());

        $validatorIterator = new ValidatorIterator([$this->getMock(
            '\eBayEnterprise\RetailOrderManagement\Payload\IValidator'
        )]);
        $stubSchemaValidator = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\ISchemaValidator');
        $payloadMap = new PayloadMap();
        $logger = new NullLogger();

        $this->lineItemIterableStub = $this->getMockBuilder(self::LINE_ITEM_ITERABLE)
            ->setConstructorArgs([$validatorIterator, $stubSchemaValidator, $payloadMap, $logger])
            ->setMethods(['getEmptyLineItem'])
            ->getMock();
        $this->lineItemIterableStub->expects($this->any())
            ->method('getEmptyLineItem')
            ->will($this->returnValue($this->lineItemStub));
        // stub helpers
        $this->coreHelper = $this->getHelperMock(
            'eb2ccore/data',
            ['getSdkApi']
        );
        $this->coreHelper->expects($this->any())
            ->method('getSdkApi')->will(
                $this->returnValueMap($this->getSdkApiMap)
            );
        $this->helper = $this->getHelperMock(
            'ebayenterprise_paypal/data',
            ['getConfigModel', '__']
        );
        $this->helper->expects($this->any())->method('__')->will(
            $this->returnArgument(0)
        );
        // stub a quote
        $strMethods = ['getCity', 'getRegionCode', 'getCountryId', 'getPostCode'];
        $this->quoteShipAddress = $this->getModelMock(
            'sales/quote_address',
            $strMethods + ['getStreet', 'getId']
        );
        foreach ($strMethods as $setter) {
            $this->quoteShipAddress->expects($this->any())
                ->method($setter)
                ->will($this->returnValue('a string value'));
        }
        $this->quoteShipAddress->expects($this->any())
            ->method('getStreet')
            ->will($this->returnValue(['line 1', 'line 2']));
        $this->quoteShipAddress->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $this->quote = $this->getModelMock(
            'sales/quote',
            ['reserveOrderId', 'getReservedOrderId', 'getAllItems', 'getTotals', 'getShippingAddress']
        );
        $this->quote->expects($this->any())
            ->method('reserveOrderId')->will($this->returnSelf());
        $this->quote->expects($this->any())
            ->method('getTotals')->will(
                $this->returnValue(
                    [
                        'grand_total' => new Varien_Object(['value' => 100]),
                        'shipping' => new Varien_Object(['value' => 5.95]),
                        'ebayenterprise_tax' => new Varien_Object(['value' => 2.50]),
                        'discount' => new Varien_Object(['value' => 100]),
                    ]
                )
            );
        $this->quote->expects($this->any())
            ->method('getReservedOrderId')->will($this->returnValue('orderid'));
        $this->quote->expects($this->any())
            ->method('getAllItems')->will(
                $this->returnValue($this->stubQuoteItems())
            );
        $this->quote->expects($this->any())
            ->method('getShippingAddress')
            ->will($this->returnValue($this->quoteShipAddress));
        $this->quote->setData(['quote_currency_code' => 'USD']);
    }

    /**
     * inject the given config into the core helper
     * @param  array  $config
     */
    protected function injectConfig(array $config)
    {
        $config = $this->buildCoreConfigRegistry(array_merge(['transferLines' => true], $config));
        $this->helper->expects($this->any())
            ->method('getConfigModel')->will($this->returnValue($config));
    }

    /**
     * mock the request and reply for the setExpressCheckout
     * @param  bool  $successful true if the reply should report success
     * @return IPayload[] (stub)
     */
    protected function mockSetExpressPayloads($successful)
    {
        $request = $this->getMock(self::SETEXPRESS_REQUEST_PAYLOAD);
        $this->stubAcceptStrReturnSelf(
            ['setOrderId', 'setReturnUrl', 'setCancelUrl', 'setLocaleCode', 'setCurrencyCode'],
            $request
        );
        $request->expects($this->once())
            ->method('setAmount')
            ->with($this->isNumeric())
            ->will($this->returnSelf());
        $request->expects($this->exactly(2))
            ->method('getLineItems')
            ->will($this->returnValue($this->lineItemIterableStub));

        $reply = $this->getMock(self::SETEXPRESS_REPLY_PAYLOAD);
        $reply->expects($this->any())
            ->method('isSuccess')
            ->will($this->returnValue($successful));
        $reply->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue('token'));

        return [$request, $reply];
    }

    /**
     * verify
     * - a request payload is acquired and sent to get a response body.
     * - the expected array structure is returned
     * - for a failure response to a successful request,
     *   throw EbayEnterprise_PayPal_Exception with a translated message.
     * @loadExpectation messageReplies
     * @dataProvider provideTrueFalse
     */
    public function testSetExpressCheckout($isSuccessful)
    {
        $this->injectConfig([
            'apiOperationSetExpressCheckout' => 'paypal/setExpress',
            'apiService'                     => 'payments',
        ]);
        list($request, $reply) = $this->mockSetExpressPayloads($isSuccessful);
        $this->sdk->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($request));
        // test setExpressCheckout
        $api = $this->getModelMock(
            'ebayenterprise_paypal/express_api',
            ['sendRequest']
        );
        $api->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($reply));
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $api,
            ['helper' => $this->helper, 'coreHelper' => $this->coreHelper]
        );
        if (!$isSuccessful) {
            $message
                = EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
            $this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
        }
        $result = $api->setExpressCheckout($this->cancelUrl, $this->returnUrl, $this->quote);
        $this->assertEquals($this->expected('setExpress')->getData(), $result);
    }

    /**
     * setup payloads for getExpressCheckout
     * @param  bool   $success
     */
    protected function mockGetExpressPayoads($success)
    {
        // mock the request
        $request = $this->getMock(self::GETEXPRESS_REQUEST_PAYLOAD);
        $this->stubAcceptStrReturnSelf(
            ['setOrderId', 'setToken', 'setCurrencyCode'],
            $request
        );
        $request->expects($this->any())
            ->method('getBillingAddressStatus')
            ->will($this->returnValue(null));
        $request->expects($this->any())
            ->method('getShipToAddressStatus')
            ->will($this->returnValue(null));

        // mock the reply
        $reply = $this->getMock(self::GETEXPRESS_REPLY_PAYLOAD);
        $reply->expects($this->any())
            ->method('isSuccess')
            ->will($this->returnValue($success));
        return [$request, $reply];
    }

    /**
     * verify
     * - get express payload is filled out
     * - the expected array structure is returned
     * - when the request was unsuccessful throw EbayEnterprise_PayPal_Exception
     * @loadExpectation messageReplies
     * @dataProvider provideTrueFalse
     */
    public function testGetExpressCheckout($isSuccessful)
    {
        $this->injectConfig(
            [
                'apiOperationGetExpressCheckout' => 'paypal/getExpress',
                'apiService' => 'payments',
            ]
        );
        list($request, $reply) = $this->mockGetExpressPayoads($isSuccessful);
        $this->sdk->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($request));
        // test setExpressCheckout
        $api = $this->getModelMock('ebayenterprise_paypal/express_api', ['sendRequest']);
        $api->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($reply));
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $api,
            ['helper' => $this->helper, 'coreHelper' => $this->coreHelper]
        );
        if (!$isSuccessful) {
            $message
                = EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
            $this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
        }
        $result = $api->getExpressCheckout($this->orderId, $this->token, $this->currencyCode);
        $this->assertEquals($this->expected('getExpress')->getData(), $result);
    }

    /**
     * verify
     * - the given sdk object is used to send the request
     * - verify the reply is returned
     */
    public function testSendRequest()
    {
        $requestPayload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\IPayload');
        $requestPayload->expects($this->any())
            ->method('serialize')
            ->will($this->returnValue('serialized request'));
        $replyPayload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\IPayload');
        $replyPayload->expects($this->any())
            ->method('serialize')
            ->will($this->returnValue('serialized reply'));
        $this->sdk->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($requestPayload));
        $this->sdk->expects($this->any())
            ->method('getResponseBody')
            ->will($this->returnValue($replyPayload));
        $api = Mage::getModel('ebayenterprise_paypal/express_api');
        $this->assertSame(
            $replyPayload,
            EcomDev_Utils_Reflection::invokeRestrictedMethod($api, 'sendRequest', [$this->sdk])
        );
    }

    /**
     * provide sdk exceptions to throw
     * @return array
     */
    public function provideSdkExceptions()
    {
        $invalidPayload = '\eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload';
        $networkError = '\eBayEnterprise\RetailOrderManagement\Api\Exception\NetworkError';
        $unsupportedOperation = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation';
        $unsupportedHttpAction = '\eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction';
        $baseException = 'Exception';
        $paypalException = 'EbayEnterprise_PayPal_Exception';
        return [
            [$invalidPayload, $paypalException],
            [$networkError, $paypalException],
            [$unsupportedOperation, $unsupportedOperation],
            [$unsupportedHttpAction, $unsupportedHttpAction],
            [$baseException, $baseException],
        ];
    }

    /**
     * GIVEN An <sdk> that will thrown an <exception> of <exceptionType> when making a request.
     * WHEN A request is made.
     * THEN The <exception> will be caught.
     * AND An exception of <expectedExceptionType> will be thrown.
     *
     * @param string
     * @param string
     * @dataProvider provideSdkExceptions
     */
    public function testSendRequestWithSdkException($exceptionType, $expectedExceptionType)
    {
        // Mock log context to prevent session interactions while getting log meta data.
        $logContext = $this->getHelperMock('ebayenterprise_magelog/context', ['getMetaData']);
        $logContext->method('getMetaData')->will($this->returnValue([]));

        $exception = new $exceptionType(__METHOD__ . ': Test Exception');

        // Set the SDK API up to throw an exception.
        $this->sdk->expects($this->any())
            ->method('send')
            ->will($this->throwException($exception));

        $api = Mage::getModel('ebayenterprise_paypal/express_api', ['log_context' => $logContext]);

        $this->setExpectedException($expectedExceptionType);

        EcomDev_Utils_Reflection::invokeRestrictedMethod($api, 'sendRequest', [$this->sdk]);
    }

    /**
     * mock the request and reply for the doExpressCheckout
     * @param  bool  $successful true if the reply should report success
     * @return IPayload[] (stub)
     */
    protected function mockDoExpressPayloads($successful)
    {
        $request = $this->getMock(self::DOEXPRESS_REQUEST_PAYLOAD);
        $this->stubAcceptStrReturnSelf(
            ['setRequestId', 'setOrderId', 'setToken', 'setPayerId', 'setCurrencyCode'],
            $request
        );
        $request->expects($this->exactly(2))
            ->method('getLineItems')
            ->will($this->returnValue($this->lineItemIterableStub));
        $request->expects($this->once())
            ->method('setAmount')
            ->with($this->isNumeric())
            ->will($this->returnSelf());

        $reply = $this->getMock(self::DOEXPRESS_REPLY_PAYLOAD);
        $reply->expects($this->any())
            ->method('isSuccess')
            ->will($this->returnValue($successful));
        return [$request, $reply];
    }

    /**
     * provide inputs for the case where:
     * - the request was succesful and does or does not have a pickup id
     * - the request was not successful
     * @return array
     */
    public function provideForDoExpressCheckout()
    {
        return [
            [true, 'pickup id'],
            [true, null],
            [false, 'pickup id'],
        ];
    }

    /**
     * verify
     *  - the expected array structure is returned
     * @param  bool $isSuccessful
     * @dataProvider provideForDoExpressCheckout
     * @loadExpectation messageReplies
     */
    public function testDoExpressCheckout($isSuccessful, $pickUpStoreId)
    {
        $this->injectConfig(
            [
                'apiOperationDoExpressCheckout' => 'paypal/doExpress',
                'apiService' => 'payments',
            ]
        );
        list($request, $reply) = $this->mockDoExpressPayloads($isSuccessful);
        if ($pickUpStoreId) {
            $request->expects($this->once())
                ->method('setPickUpStoreId')
                ->with($this->isType('string'))
                ->will($this->returnSelf());
        }
        $this->sdk->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($request));
        // test setExpressCheckout
        $api = $this->getModelMock('ebayenterprise_paypal/express_api', ['sendRequest']);
        $api->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($reply));
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $api,
            ['helper' => $this->helper, 'coreHelper' => $this->coreHelper]
        );
        if (!$isSuccessful) {
            $message
                = EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
            $this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
        }
        $result = $api->doExpressCheckout($this->quote, $this->token, $this->payerId, $pickUpStoreId);
        $this->assertEquals($this->expected('doExpress')->getData(), $result);
    }

    /**
     * stub items for the tests.
     * @return Mage_Sale_Model_Quote_Item[]
     */
    protected function stubQuoteItems()
    {
        $methods = [
            'getHasChildren', 'isChildrenCalculated', 'getChildren', 'getProduct',
            'getId', 'getQuoteItem', 'getQty', 'getPrice', 'getName',
        ];
        // item mocks
        $item = $this->getModelMock('sales/quote_item', $methods);
        $parent = $this->getModelMock('sales/quote_item', $methods);
        $child = $this->getModelMock('sales/quote_item', $methods);

        $parent->expects($this->any())
            ->method('getHasChildren')
            ->will($this->returnValue(true));
        $parent->expects($this->any())
            ->method('isChildrenCalculated')
            ->will($this->returnValue(true));
        $parent->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue([$child]));

        $stubs = [$item, $child, $parent];
        foreach ($stubs as $stub) {
            $stub->expects($this->any())
                ->method('getProduct')
                ->will($this->returnSelf());
            $stub->expects($this->any())
                ->method('getId')
                ->will($this->returnValue(key($stubs)));
            $stub->expects($this->any())
                ->method('getQuoteItem')
                ->will($this->returnSelf());
            $stub->expects($this->any())
                ->method('getQty')
                ->will($this->returnValue(1));
            $stub->expects($this->any())
                ->method('getPrice')
                ->will($this->returnValue(1));
        }

        $item->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('item'));
        $parent->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('parent'));
        $child->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('child'));

        return [$item, $parent];
    }

    /**
     * creat a constraint to assert an argument has a numeric value
     * @return PHPUnit_Framework_Constraint
     */
    protected function isNumeric()
    {
        return $this->callback(
            function ($val) {

                return is_numeric($val);
            }
        );
    }

    /**
     * mock the request and reply for the doAuthorization message
     * @param  bool  $successful true if the reply should report success
     * @return IPayload[] (stub)
     */
    protected function mockDoAuthorizationPayloads($successful)
    {
        $request = $this->getMock(self::DOAUTH_REQUEST_PAYLOAD);
        $this->stubAcceptStrReturnSelf(
            ['setRequestId', 'setOrderId', 'setCurrencyCode'],
            $request
        );
        $request->expects($this->once())
            ->method('setAmount')
            ->with($this->isNumeric())
            ->will($this->returnSelf());

        $reply = $this->getMock(self::DOAUTH_REPLY_PAYLOAD);
        $reply->expects($this->any())
            ->method('isSuccess')
            ->will($this->returnValue($successful));
        return [$request, $reply];
    }

    /**
     * verify
     * - the request paylad is setup with the correct type of data
     * - the resulting array is in the expected structure
     * - the is_authorized field is true if the message succeeded; false otherwise
     * - throws an EbayEnterprise_PayPal_Exception when the message fails
     * @loadExpectation messageReplies
     * @dataProvider provideTrueFalse
     */
    public function testDoAuthorization($isSuccessful)
    {
        $this->injectConfig(
            [
                'apiOperationDoAuthorization' => 'paypal/doAuth',
                'apiService'                  => 'payments',
            ]
        );
        list($request, $reply) = $this->mockDoAuthorizationPayloads($isSuccessful);
        $this->sdk->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($request));
        // test setExpressCheckout
        $api = $this->getModelMock('ebayenterprise_paypal/express_api', ['sendRequest']);
        $api->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($reply));
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $api,
            ['helper' => $this->helper, 'coreHelper' => $this->coreHelper]
        );
        if (!$isSuccessful) {
            $message
                = EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
            $this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
        }
        $result = $api->doAuthorization($this->quote);
        $expectation = 'doAuth/' . ($isSuccessful ? 'success' : 'failure');
        $this->assertEquals($this->expected($expectation)->getData(), $result);
    }

    /**
     * mock the request and reply for the doVoid message
     * @param  bool  $successful true if the reply should report success
     * @return IPayload[] (stub)
     */
    protected function mockDoVoidPayloads($successful)
    {
        $request = $this->getMock(self::DOVOID_REQUEST_PAYLOAD);
        $request->expects($this->once())
            ->method('setRequestId')
            ->with($this->isType('string'))
            ->will($this->returnSelf());
        $request->expects($this->once())
            ->method('setOrderId')
            ->with($this->isType('string'))
            ->will($this->returnSelf());

        $reply = $this->getMock(self::DOVOID_REPLY_PAYLOAD);
        $reply->expects($this->any())
            ->method('isSuccess')
            ->will($this->returnValue($successful));
        return [$request, $reply];
    }

    /**
     * verify
     * - the request paylad is setup with the correct type of data
     * - the resulting array is in the expected structure
     * - the is_voided field is true if the message succeeded; false otherwise
     * - throws an EbayEnterprise_PayPal_Exception when the message fails
     * @loadExpectation messageReplies
     * @dataProvider provideTrueFalse
     */
    public function testDoVoid($isSuccessful)
    {
        $order = $this->getModelMock('sales/order', ['getIncrementId']);
        $order->expects($this->any())
            ->method('getIncrementId')
            ->will($this->returnValue($this->orderId));

        $this->injectConfig(['apiOperationDoVoid' => 'paypal/void', 'apiService' => 'payments']);
        list($request, $reply) = $this->mockDoVoidPayloads($isSuccessful);
        $this->sdk->expects($this->any())
            ->method('getRequestBody')
            ->will($this->returnValue($request));
        // test setExpressCheckout
        $api = $this->getModelMock('ebayenterprise_paypal/express_api', ['sendRequest']);
        $api->expects($this->any())
            ->method('sendRequest')
            ->will($this->returnValue($reply));
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $api,
            ['helper' => $this->helper, 'coreHelper' => $this->coreHelper]
        );
        if (!$isSuccessful) {
            $message
                = EbayEnterprise_PayPal_Model_Express_Api::EBAYENTERPRISE_PAYPAL_API_FAILED;
            $this->setExpectedException('EbayEnterprise_PayPal_Exception', $message);
        }
        $result = $api->doVoidOrder($order);
        $expectation = 'void/' . ($isSuccessful ? 'success' : 'failure');
        $this->assertEquals($this->expected($expectation)->getData(), $result);
    }

    /**
     * use to assert an argument is a scalar
     * @return PHPUnit_Framework_Constraint
     */
    protected function isScalar()
    {
        return $this->callback(
            function ($val) {

                return is_scalar($val);
            }
        );
    }

    /**
     * stub the specified methods on the mock to expect a
     * string argument and return itself
     * @param  array  $methods
     * @param  object $stub
     * @return object
     */
    protected function stubAcceptStrReturnSelf(array $methods, $stub)
    {
        foreach ($methods as $method) {
            $stub->expects($this->any())
                ->method($method)
                ->with($this->isType('string'))
                ->will($this->returnSelf());
        }
        return $stub;
    }

    /**
     * ensure the tax amount gets added
     *
     */
    public function testAddLineItems()
    {
        $iterable = $this->getMockBuilder('\eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemIterable')
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(self::SETEXPRESS_REQUEST_PAYLOAD)
            ->getMockForAbstractClass();
        $api = $this->getModelMockBuilder('ebayenterprise_paypal/express_api')
            ->setConstructorArgs([['core_helper' => $this->coreHelper]])
            ->setMethods(['processLineItems', 'processNegativeLineItems', 'canIncludeLineItems'])
            ->getMock();

        $api->expects($this->once())
            ->method('processLineItems')
            ->will($this->returnSelf());
        $api->expects($this->once())
            ->method('processNegativeLineItems')
            ->will($this->returnSelf());
        $api->expects($this->once())
            ->method('canIncludeLineItems')
            ->will($this->returnValue(true));

        $request->expects($this->atLeastOnce())
            ->method('getLineItems')
            ->will($this->returnValue($iterable));

        $request->expects($this->once())
            ->method('setTaxTotal')
            ->with($this->identicalTo(2.50))
            ->will($this->returnSelf());
        $request->expects($this->once())
            ->method('setShippingTotal')
            ->will($this->returnSelf());
        $request->expects($this->once())
            ->method('setCurrencyCode')
            ->will($this->returnSelf());
        EcomDev_Utils_Reflection::invokeRestrictedMethod($api, 'addLineItems', [$this->quote, $request]);
    }

    /**
     * verify line items are created from
     *
     */
    public function testProcessLineItems()
    {
        $item = $this->getModelMockBuilder('sales/quote_item_abstract')
            ->getMockForAbstractClass();
        $quote = $this->getModelMockBuilder('sales/quote')
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'getQuoteCurrencyCode'])
            ->getMock();
        $lineItems = $this->getMockBuilder(
            '\eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemIterable'
        )
            ->getMockForAbstractClass();
        $selectionHelper = $this->getHelperMock(
            'ebayenterprise_paypal/item_selection',
            ['selectFrom']
        );
        $currencyCode = 'USD';

        $quote->expects($this->once())
            ->method('getAllItems')
            ->will($this->returnValue([$item]));
        $quote->expects($this->once())
            ->method('getQuoteCurrencyCode')
            ->will($this->returnValue($currencyCode));
        $selectionHelper->expects($this->once())
            ->method('selectFrom')
            ->with($this->equalTo([$item]))
            ->will($this->returnArgument(0));

        $api = $this->getModelMockBuilder('ebayenterprise_paypal/express_api')
            ->setConstructorArgs([['selection_helper' => $selectionHelper]])
            ->setMethods(['createLineItem'])
            ->getMock();
        $api->expects($this->once())
            ->method('createLineItem')
            ->with(
                $this->identicalTo($item),
                $this->identicalTo($lineItems),
                $this->identicalTo($currencyCode)
            );
        EcomDev_Utils_Reflection::invokeRestrictedMethod(
            $api,
            'processLineItems',
            [$quote, $lineItems]
        );
    }
}
