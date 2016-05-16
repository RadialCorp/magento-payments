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
 * Test Order Create
 */
class EbayEnterprise_PayPal_Test_Model_Order_Create_PaymentTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    const PAYMENT_CONTAINER_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer';
    const PAYMENT_ITERABLE_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentIterable';
    const PAYMENT_PAYLOAD_CLASS =
        '\eBayEnterprise\RetailOrderManagement\Payload\Order\IPayPalPayment';

    /** @var Mage_Sales_Model_Order (stub) */
    protected $_orderStub;
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer */
    protected $_paymentContainer;
    /** @var array stubbed payment models */
    protected $_paymentStubs = [];
    /** @var \eBayEnterprise\RetailOrderManagement\Payload\Order\IPayPalPayment */
    protected $_payloadStub;

    public function setUp()
    {
        $this->_payloadStub = $this->getMock(self::PAYMENT_PAYLOAD_CLASS);
        $paymentIterable = $this->getMock(self::PAYMENT_ITERABLE_CLASS);
        $paymentIterable->expects($this->any())
            ->method('getEmptyPayPalPayment')
            ->will($this->returnValue($this->_payloadStub));
        $this->_paymentContainer = $this->getMock(self::PAYMENT_CONTAINER_CLASS);
        $this->_paymentContainer->expects($this->any())
            ->method('getPayments')
            ->will($this->returnValue($paymentIterable));

        $payment1 = $this->getModelMock('sales/order_payment', ['getId']);
        $payment1->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $payment1->setCreatedAt('2012-01-01 00:00:00')
            ->setMethod(Mage::getModel('ebayenterprise_paypal/method_express')->getCode());

        $payment2 = $this->getModelMock('sales/order_payment', ['getId']);
        $payment2->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $payment2->setMethod('non-paypal');

        $this->_paymentStubs[] = $payment1;
        $this->_paymentStubs[] = $payment2;

        $this->_orderStub = $this->getModelMock('sales/order', ['getAllPayments']);
        $this->_orderStub->expects($this->once())
            ->method('getAllPayments')
            ->will($this->returnValue($this->_paymentStubs));
    }

    /**
     * verify
     * - payment will be made into payload
     * - the brand will not be set for a payment with no cc type configured
     */
    public function testAddPaymentsToPayload()
    {
        $processedPayments = new SplObjectStorage();
        $processedPayments->attach($this->_paymentStubs[1]);
        $methods = [
            'setAmount',
            'setAmountAuthorized',
            'setCreateTimestamp',
            'setAuthorizationResponseCode',
            'setOrderId',
            'setTenderType',
            'setPanIsToken',
            'setAccountUniqueId',
            'setPaymentRequestId',
        ];
        foreach ($methods as $method) {
            $this->_payloadStub->expects($this->once())
                ->method($method)
                ->will($this->returnSelf());
        }
        $handler = Mage::getModel('ebayenterprise_paypal/order_create_payment');
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $processedPayments);
    }

    /**
     * verify
     * - no payments will be processed if all are in the
     *   processed list
     */
    public function testAddPaymentsToPayloadAlreadyProcessed()
    {
        $processedPayments = new SplObjectStorage();
        $processedPayments->attach($this->_paymentStubs[0]);
        $this->_payloadStub->expects($this->never())
            ->method('setAmount');
        $handler = Mage::getModel('ebayenterprise_paypal/order_create_payment');
        $handler->addPaymentsToPayload($this->_orderStub, $this->_paymentContainer, $processedPayments);
    }
}
