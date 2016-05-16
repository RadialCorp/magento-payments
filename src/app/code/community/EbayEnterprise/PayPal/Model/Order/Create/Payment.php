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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IPaymentContainer;

class EbayEnterprise_PayPal_Model_Order_Create_Payment
{
    const AUTH_RESPONSE_CODE = 'APPROVED';
    const TENDER_TYPE = 'PY';
    const ACCOUNT_UNIQUE_ID = 'PAYPAL';

    /**
     * Make prepaid credit card payloads for any payments
     * remaining in the list
     * @param Mage_Sales_Model_Order $order
     * @param IPaymentContainer      $paymentContainer
     * @param SplObjectStorage       $processedPayments
     */
    public function addPaymentsToPayload(
        Mage_Sales_Model_Order $order,
        IPaymentContainer $paymentContainer,
        SplObjectStorage $processedPayments
    ) {
        foreach ($order->getAllPayments() as $payment) {
            if ($this->_shouldIgnorePayment($payment, $processedPayments)) {
                continue;
            }
            $iterable = $paymentContainer->getPayments();
            $payload = $iterable->getEmptyPayPalPayment();
            $additionalInfo = new Varien_Object($payment->getAdditionalInformation());
            // use the grand total since it has already been adjusted for redeemed giftcards
            // by the the giftcard module's total collector.
            $amount = $order->getGrandTotal();
            $payload
                ->setAmount($amount)
                ->setAmountAuthorized($amount)
                ->setCreateTimestamp($this->_getAsDateTime($payment->getCreatedAt()))
                ->setAuthorizationResponseCode(self::AUTH_RESPONSE_CODE)
                ->setOrderId($order->getIncrementId())
                ->setTenderType(self::TENDER_TYPE)
                ->setPanIsToken(true)
                ->setAccountUniqueId(self::ACCOUNT_UNIQUE_ID)
                ->setPaymentRequestId($additionalInfo->getAuthRequestId());
            // add the new payload
            $iterable->OffsetSet($payload, $payload);
            // put the payment in the processed payments set
            $processedPayments->attach($payment);
        }
    }

    protected function _getQuotePayment(Mage_Sales_Model_Order_Payment $payment)
    {
        return Mage::getModel('sales/quote_payment')
            ->load($payment->getQuotePaymentId());
    }

    /**
     * convert a mage string date to a datetime
     * @param  string $dateString
     * @return DateTime
     */
    protected function _getAsDateTime($dateString)
    {
        return DateTime::createFromFormat('Y-m-d H:i:s', $dateString);
    }

    /**
     * return true if the payment should not be processed
     *
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @param  SplObjectStorage               $processedPayments
     * @return bool
     */
    protected function _shouldIgnorePayment(Mage_Sales_Model_Order_Payment $payment, SplObjectStorage $processedPayments)
    {
        return isset($processedPayments[$payment]) ||
            $payment->getMethod() !== Mage::getModel('ebayenterprise_paypal/method_express')->getCode();
    }
}
