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
 * Test Order Create Payment Payload Injection
 */
class EbayEnterprise_CreditCard_Test_Model_Order_Create_PaymentTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const PAYMENT_CONTAINER_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer';
    const PAYMENT_ITERABLE_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentIterable';
    const PAYMENT_PAYLOAD_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\ICreditCardPayment';

    /** @var Mage_Sales_Model_Order (stub) */
    protected $_orderStub;
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer */
    protected $_paymentContainer;
    /** @var Mage_Payment_Model_Info */
    protected $_paymentStub;
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPayPalPayment */
    protected $_payloadStub;

    public function setUp()
    {
        $this->_payloadStub = $this->getMock(self::PAYMENT_PAYLOAD_CLASS);
        $paymentIterable = $this->getMock(self::PAYMENT_ITERABLE_CLASS);
        $paymentIterable->expects($this->any())
            ->method('getEmptyCreditCardPayment')
            ->will($this->returnValue($this->_payloadStub));
        $this->_paymentContainer = $this->getMock(self::PAYMENT_CONTAINER_CLASS);
        $this->_paymentContainer->expects($this->any())
            ->method('getPayments')
            ->will($this->returnValue($paymentIterable));

        $methodCode = Mage::getModel('ebayenterprise_creditcard/method_ccpayment')
            ->getCode();
        $this->_paymentStub = $this->getModelMock('sales/order_payment', ['getId', 'getCcNumberEnc', 'decrypt']);
        $this->_paymentStub
            ->setMethod($methodCode)
            ->setCreatedAt('2012-01-01 00:00:00')
            ->setCcExpYear(2020)
            ->setCcExpMonth(1)
            ->setAdditionalInformation('start_date', '2012-01-01 00:00:00');

        $this->_orderStub = $this->getModelMock('sales/order', ['getAllPayments']);
        $this->_orderStub->expects($this->once())
            ->method('getAllPayments')
            ->will($this->returnValue([$this->_paymentStub]));
    }
    /**
     * verify
     * - payment will be made into a payload using the pan
     */
    public function testAddPaymentsToPayloadUsePan()
    {
        $processedPayments = new SplObjectStorage();
        $this->_paymentStub->setAdditionalInformation('pan', 'pan');
        $this->_stubPayload(['setAccountUniqueId']);
        $this->_payloadStub->expects($this->once())
            ->method('setAccountUniqueId')
            ->with($this->identicalTo('pan'))
            ->will($this->returnSelf());
        $handler = Mage::getModel('ebayenterprise_creditcard/order_create_payment');
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $processedPayments);
    }
    /**
     * verify
     * - payment will be made into a payload using the encrypted card number
     */
    public function testAddPaymentsToPayloadUseEcryptedCardNumber()
    {
        $processedPayments = new SplObjectStorage();
        $this->_paymentStub->expects($this->any())
            ->method('getCcNumberEnc')
            ->will($this->returnValue('enc-cc-num'));
        $this->_paymentStub->expects($this->any())
            ->method('decrypt')
            ->will($this->returnValue('enc-cc-num'));
        $this->_stubPayload(['setAccountUniqueId']);
        $this->_payloadStub->expects($this->once())
            ->method('setAccountUniqueId')
            ->with($this->identicalTo('enc-cc-num'))
            ->will($this->returnSelf());
        $handler = Mage::getModel('ebayenterprise_creditcard/order_create_payment');
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $processedPayments);
    }
    /**
     * verify
     * - non ebayenterprise_creditcard payment will not be made into a payload
     */
    public function testAddPaymentsToPayloadNonCreditCardPayment()
    {
        $processedPayments = new SplObjectStorage();
        $this->_paymentStub->setMethod('non-ebayenterprise_creditcard');
        $this->_payloadStub->expects($this->never())
            ->method('setOrderId');
        $handler = Mage::getModel('ebayenterprise_creditcard/order_create_payment');
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $processedPayments);
    }
    /**
     * verify
     * - payments in set of processed payments will not be made into a payload
     */
    public function testAddPaymentsToPayloadAlreadyProcessed()
    {
        $processedPayments = new SplObjectStorage();
        $processedPayments->attach($this->_paymentStub);
        $this->_payloadStub->expects($this->never())
            ->method('setOrderId');
        $handler = Mage::getModel('ebayenterprise_creditcard/order_create_payment');
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $processedPayments);
    }
    /**
     * stub out the setter methods for the payload excluding those
     * specified in the array
     * @param  array  $exclude
     */
    protected function _stubPayload($exclude = [])
    {
        $methods = [
            'setOrderId',
            'setTenderType',
            'setAccountUniqueId',
            'setPanIsToken',
            'setPaymentRequestId',
            'setCreateTimestamp',
            'setAmount',
            'setResponseCode',
            'setBankAuthorizationCode',
            'setCVV2ResponseCode',
            'setAVSResponseCode',
            'setPhoneResponseCode',
            'setNameResponseCode',
            'setEmailResponseCode',
            'setAmountAuthorized',
            'setExpirationDate',
            'setExtendedAuthDescription',
            'setExtendedAuthReasonCode',
            'setStartDate',
            'setIssueNumber',
            'setAuthenticationAvailable',
            'setAuthenticationStatus',
            'setCavvUcaf',
            'setTransactionId',
            'setECI',
            'setPayerAuthenticationResponse',
            'setPurchasePlanCode',
            'setPurchasePlanDescription',
        ];
        foreach ($methods as $method) {
            if (!in_array($method, $exclude)) {
                $this->_payloadStub->expects($this->once())
                    ->method($method)
                    ->will($this->returnSelf());
            }
        }
    }
}
