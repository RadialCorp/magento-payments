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

class EbayEnterprise_CreditCard_Model_Order_Create_Payment
{
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
            $payload = $iterable->getEmptyCreditCardPayment();
            $additionalInfo = new Varien_Object($payment->getAdditionalInformation());
            $payload
                // payment context
                ->setOrderId($order->getIncrementId())
                ->setTenderType($additionalInfo->getTenderType())
                ->setAccountUniqueId($this->_getAccountUniqueId($payment))
                ->setPanIsToken($additionalInfo->getPanIsToken())
                ->setPaymentRequestId($additionalInfo->getRequestId())
                // payment data
                ->setCreateTimestamp($this->_getAsDateTime($payment->getCreatedAt()))
                ->setAmount($payment->getAmountAuthorized())
                ->setBankAuthorizationCode($additionalInfo->getBankAuthorizationCode())
                ->setResponseCode($additionalInfo->getResponseCode())
                ->setCVV2ResponseCode($additionalInfo->getCvv2ResponseCode())
                ->setAVSResponseCode($additionalInfo->getAvsResponseCode())
                ->setPhoneResponseCode($additionalInfo->getPhoneResponseCode())
                ->setNameResponseCode($additionalInfo->getNameResponseCode())
                ->setEmailResponseCode($additionalInfo->getEmailResponseCode())
                ->setAmountAuthorized($payment->getAmountAuthorized())
                ->setExpirationDate($this->_getExpirationDateTime($payment))
                // extra fields for future implementation
                ->setExtendedAuthDescription($additionalInfo->getExtendedAuthDescription())
                ->setExtendedAuthReasonCode($additionalInfo->getExtendedAuthReasonCode())
                ->setIssueNumber($additionalInfo->getIssueNumber())
                ->setAuthenticationAvailable($additionalInfo->getAuthenticationAvailable())
                ->setAuthenticationStatus($additionalInfo->getAuthenticationStatus())
                ->setCavvUcaf($additionalInfo->getCavvUcaf())
                ->setTransactionId($additionalInfo->getTransactionId())
                ->setECI($additionalInfo->getECI())
                ->setPayerAuthenticationResponse($additionalInfo->getPayerAuthenticationResponse())
                ->setPurchasePlanCode($additionalInfo->getPurchasePlanCode())
                ->setPurchasePlanDescription($additionalInfo->getPurchasePlanDescription());
            if ($additionalInfo->getStartDate()) {
                // prevent death by type error if getStartDate returns null
                $payload->setStartDate($this->_getAsDateTime($additionalInfo->getStartDate()));
            }
            // add the new payload
            $iterable->OffsetSet($payload, $payload);
            // put the payment in the processed payments set
            $processedPayments->attach($payment);
        }
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
     * get the expiration date as a datetime object
     * @param  Mage_Payment_Model_Info $payment
     * @return DateTime
     */
    protected function _getExpirationDateTime(Mage_Payment_Model_Info $payment)
    {
        return DateTime::createFromFormat(
            'Y-m',
            sprintf('%d-%02d', $payment->getCcExpYear(), $payment->getCcExpMonth())
        );
    }
    /**
     * Get the encrypted card number or the ROM PAN
     * @param  Mage_Payment_Model_Info $payment
     * @return string
     */
    protected function _getAccountUniqueId(Mage_Payment_Model_Info $payment)
    {
        $encCardNumber = $payment->getCcNumberEnc();
        if ($encCardNumber) {
            return $payment->decrypt($encCardNumber);
        }
        return $payment->getAdditionalInformation('pan');
    }
    /**
     * return true if the payment should not be processed
     *
     * @param  Mage_Sales_Model_Order_Payment $payment
     * @param  SplObjectStorage               $processedPayments
     * @return bool
     */
    protected function _shouldIgnorePayment(Mage_Payment_Model_Info $payment, SplObjectStorage $processedPayments)
    {
        $methodCode = Mage::getModel('ebayenterprise_creditcard/method_ccpayment')
            ->getCode();
        return $processedPayments->OffsetExists($payment) ||
            $payment->getMethod() !== $methodCode;
    }
}
