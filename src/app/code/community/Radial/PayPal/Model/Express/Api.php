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
use eBayEnterprise\RetailOrderManagement\Api\IBidirectionalApi;
use eBayEnterprise\RetailOrderManagement\Payload\Exception\InvalidPayload;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\IShippingAddress;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemIterable;
use eBayEnterprise\RetailOrderManagement\Payload\Payment\ILineItemContainer;
use eBayEnterprise\RetailOrderManagement\Api;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedHttpAction;
use eBayEnterprise\RetailOrderManagement\Api\Exception\UnsupportedOperation;
use eBayEnterprise\RetailOrderManagement\Payload;
/**
 * Payment Method for PayPal payments through Retail Order Management.
 * @SuppressWarnings(TooManyMethods)
 */
class Radial_Paypal_Model_Express_Api
{
    const RADIAL_PAYPAL_API_FAILED = 'RADIAL_PAYPAL_API_FAILED';
    const RADIAL_PAYPAL_DENIED_MESSAGE = 'RADIAL_PAYPAL_DENIED_MESSAGE';
    const RADIAL_PAYPAL_CONFIRM_FUNDS_TIMEOUT = 'RADIAL_PAYPAL_CONFIRM_FUNDS_TIMEOUT';

    const PAYPAL_SETEXPRESS_REQUEST_ID_PREFIX = 'PSE-';
    const PAYPAL_GETEXPRESS_REQUEST_ID_PREFIX = 'PSG-';
    const PAYPAL_DOEXPRESS_REQUEST_ID_PREFIX = 'PSD-';
    const PAYPAL_DOAUTHORIZATION_REQUEST_ID_PREFIX = 'PSA-';
    const PAYPAL_DOVOID_REQUEST_ID_PREFIX = 'PSV-';
    const SETTLEMENT_TYPE_CAPTURE = 'Debit';
    const SETTLEMENT_TYPE_REFUND = 'Credit';
    const PAYMENT_CONFIRM_FUNDS_FAILED = 'RADIAL_PAYPAL_PAYMENT_CONFIRM_FUNDS_FAILED';
    const TENDER_TYPE_PAYPAL = 'PY';

    /** @var Radial_PayPal_Helper_Item_Selection */
    protected $selectionHelper;
    /** @var Radial_PayPal_Helper_Data */
    protected $helper;
    /** @var Radial_Core_Helper_Data */
    protected $coreHelper;
    /** @var Radial_Payments_Helper_Data */
    protected $paymentsHelper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $logContext;
    /** @var Radial_Core_Model_Config_Registry */
    protected $config;

    /**
     * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
     * Override __construct here as the usual protected `_construct` is not called.
     *
     * @param array $initParams May contain:
     *     -  'selection_helper' => Radial_PayPal_Helper_Item_Selection
     *     -  'helper' => Radial_PayPal_Helper_Data
     *     -  'core_helper' => Radial_Core_Helper_Data
     *     -  'payments_helper' => Radial_Payments_Helper_Data
     *     -  'logger' => EbayEnterprise_MageLog_Helper_Data
     *     -  'context' => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $initParams = array())
    {
        list(
            $this->selectionHelper,
            $this->helper,
            $this->coreHelper,
            $this->paymentsHelper,
            $this->logger,
            $this->logContext
        ) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'selection_helper', Mage::helper('radial_paypal/item_selection')),
            $this->nullCoalesce($initParams, 'helper', Mage::helper('radial_paypal')),
            $this->nullCoalesce($initParams, 'core_helper', Mage::helper('radial_core')),
            $this->nullCoalesce($initParams, 'payments_helper', Mage::helper('radial_payments')),
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'log_context', Mage::helper('ebayenterprise_magelog/context'))
        );
        $this->config = $this->helper->getConfigModel();
        $this->_isUsingClientSideEncryption = $this->config->useClientSideEncryptionFlag;
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param Radial_PayPal_Helper_Item_Selection
     * @param Radial_PayPal_Helper_Data
     * @param Radial_Core_Helper_Data
     * @param Radial_Payments_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        Radial_PayPal_Helper_Item_Selection $selectionHelper,
        Radial_PayPal_Helper_Data $helper,
        Radial_Core_Helper_Data $coreHelper,
        Radial_Payments_Helper_Data $paymentsHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $logContext
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
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
    
    public function doCapture($invoice, $payment)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoSettlement,
            [static::TENDER_TYPE_PAYPAL]
        );
        $this->_prepareCaptureRequest($sdk, $invoice, $payment);
        Mage::dispatchEvent('radial_paypal_capture_request_send_before', [
            'payload' => $sdk->getRequestBody(),
            'payment' => $payment,
            'invoice' => $invoice,
        ]);
        // Log the request instead of expecting the SDK to have logged it.
        // Allows the data to be properly scrubbed of any PII or other sensitive
        // data prior to writing the log files.
        $logMessage = 'Sending paypal capture request.';
        $this->logger->debug($logMessage, $this->logContext->getMetaData(__CLASS__, ['request_body' => $sdk->getRequestBody()->serialize()]));
        $this->sendRequest($sdk);
        // Log the response instead of expecting the SDK to have logged it.
        // Allows the data to be properly scrubbed of any PII or other sensitive
        // data prior to writing the log files.
        $logMessage = 'Received paypal capture response.';
        $this->logger->debug($logMessage, $this->logContext->getMetaData(__CLASS__, ['response_body' => $sdk->getResponseBody()->serialize()]));
        $this->_handleCaptureResponse($sdk, $invoice);
        return $this;
    }
    
    public function doRefund($creditmemo, $payment)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoSettlement,
            [static::TENDER_TYPE_PAYPAL]
        );
        $this->_prepareRefundRequest($sdk, $creditmemo, $payment);
        Mage::dispatchEvent('radial_paypal_refund_request_send_before', [
            'payload' => $sdk->getRequestBody(),
            'payment' => $payment,
        ]);
        // Log the request instead of expecting the SDK to have logged it.
        // Allows the data to be properly scrubbed of any PII or other sensitive
        // data prior to writing the log files.
        $logMessage = 'Sending paypal refund request.';
        $this->logger->debug($logMessage, $this->logContext->getMetaData(__CLASS__, ['request_body' => $sdk->getRequestBody()->serialize()]));
        $this->sendRequest($sdk);
        // Log the response instead of expecting the SDK to have logged it.
        // Allows the data to be properly scrubbed of any PII or other sensitive
        // data prior to writing the log files.
        $logMessage = 'Received paypal refund response.';
        $this->logger->debug($logMessage, $this->logContext->getMetaData(__CLASS__, ['response_body' => $sdk->getResponseBody()->serialize()]));
        $this->_handleRefundResponse($sdk, $creditmemo, $payment);
        return $this;
    }
    /**
     * Send confirm funds request
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return self
     * @throws Mage_Core_Exception
     */
    public function doConfirm(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException($this->helper->__('Invalid amount to confirm funds.'));
        }
        $sdk = $this->getSdk(
            $this->config->apiOperationDoConfirmFunds,
            [static::TENDER_TYPE_PAYPAL]
        );
        $this->_prepareConfirmFundsRequest($sdk, $payment, $amount);
        Mage::dispatchEvent('radial_creditcard_confirm_funds_send_before', [
            'payload' => $sdk->getRequestBody(),
            'payment' => $payment,
        ]);
        // Log the request instead of expecting the SDK to have logged it.
        // Allows the data to be properly scrubbed of any PII or other sensitive
        // data prior to writing the log files.
        $logMessage = 'Sending paypal confirm funds request.';
        $this->logger->debug($logMessage, $this->logContext->getMetaData(__CLASS__, ['request_body' => $sdk->getRequestBody()->serialize()]));
        $this->sendRequest($sdk);
        // Log the response instead of expecting the SDK to have logged it.
        // Allows the data to be properly scrubbed of any PII or other sensitive
        // data prior to writing the log files.
        $logMessage = 'Received paypal confirm funds response.';
        $this->logger->debug($logMessage, $this->logContext->getMetaData(__CLASS__, ['response_body' => $sdk->getResponseBody()->serialize()]));
        $this->_handleConfirmFundsResponse($sdk, $payment);
        return $this;
    }

    /**
     * @param Api\IBidirectionalApi $sdk
     * @param Mage_Sales_Model_Order_Invoice
     * @return self
     */
    protected function _handleCaptureResponse(Api\IBidirectionalApi $sdk, Mage_Sales_Model_Order_Invoice $invoice)
    {
        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID);
	$invoice->pay();
        return $this;
    }
    /**
     * @param Api\IBidirectionalApi $sdk
     * @param Mage_Sales_Model_Order_Creditmemo
     * @param Mage_Sales_Model_Order_Payment
     * @return self
     */
    protected function _handleRefundResponse(Api\IBidirectionalApi $sdk, Mage_Sales_Model_Order_Creditmemo $creditmemo, Mage_Sales_Model_Order_Payment $payment)
    {
        $creditmemo->setState(Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED);
        return $this;
    }
    /**
     * Update payment objects with details of the confirm request and response. Validate
     * that a successful response was received.
     * @param Api\IBidirectionalApi $sdk
     * @param Varien_Object        $payment
     * @return self
     */
    protected function _handleConfirmFundsResponse(Api\IBidirectionalApi $sdk, Varien_Object $payment)
    {
        /** @var Payload\Payment\ConfirmFundsReply $response */
        $response = $sdk->getResponseBody();
        return $this->_validateConfirmFundsResponse($response, $payment);
    }
    /**
     * Fill out the request payload with payment data and update the API request
     * body with the complete request.
     * @param Api\IBidirectionalApi $sdk
     * @param Varien_Object $payment Most likely a Mage_Sales_Model_Order_Payment
     * @param string $type
     * @return static
     */
    protected function _prepareCaptureRequest(Api\IBidirectionalApi $sdk, $invoice, $payment)
    {
        $this->logger->debug($invoice->getTransactionId());
        /** @var Payload\Payment\PaymentSettlementRequest $request */
        $request = $sdk->getRequestBody();
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $request
            ->setAmount((float)$invoice->getGrandTotal())
            ->setCurrencyCode(Mage::app()->getStore()->getBaseCurrencyCode())
            ->setTaxAmount((float)$invoice->getTaxAmount())
            ->setClientContext($invoice->getTransactionId())
            ->setRequestId($this->coreHelper->generateRequestId('CCA-'))
            ->setSettlementType(self::SETTLEMENT_TYPE_CAPTURE)
            ->setFinalDebit($this->paymentsHelper->isFinalDebit($order) ? 1 : 0)
            ->setInvoiceId($invoice->getIncrementId())
            ->setOrderId($order->getIncrementId());
        return $this;
    }
    /**
     * Fill out the request payload with payment data and update the API request
     * body with the complete request.
     * @param Api\IBidirectionalApi $sdk
     * @param Varien_Object $payment Most likely a Mage_Sales_Model_Order_Payment
     * @param string $type
     * @return static
     */
    protected function _prepareRefundRequest(Api\IBidirectionalApi $sdk, $creditmemo, $payment)
    {
        /** @var Payload\Payment\PaymentSettlementRequest $request */
        $request = $sdk->getRequestBody();
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $request
            ->setAmount((float)$creditmemo->getGrandTotal())
            ->setCurrencyCode(Mage::app()->getStore()->getBaseCurrencyCode())
            ->setTaxAmount((float)$creditmemo->getTaxAmount())
            ->setClientContext($creditmemo->getTransactionId())
            ->setRequestId($this->coreHelper->generateRequestId('CCA-'))
            ->setSettlementType(self::SETTLEMENT_TYPE_REFUND)
            ->setFinalDebit(0)
            ->setInvoiceId($creditmemo->getIncrementId())
            ->setOrderId($order->getIncrementId());
        return $this;
    }
    /**
     * Fill out the request payload with payment data and update the API request
     * body with the complete request.
     * @param Api\IBidirectionalApi $sdk
     * @param Varien_Object $payment Most likely a Mage_Sales_Model_Order_Payment
     * @param $amount
     * @return static
     */
    protected function _prepareConfirmFundsRequest(Api\IBidirectionalApi $sdk, Varien_Object $payment, $amount)
    {
        /** @var Payload\Payment\ConfirmFundsRequest $request */
        $request = $sdk->getRequestBody();
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $request
            ->setAmount((float)$amount)
            ->setCurrencyCode(Mage::app()->getStore()->getBaseCurrencyCode())
            ->setRequestId($this->coreHelper->generateRequestId('CCA-'))
            ->setOrderId($order->getIncrementId())
	    ->setPerformReauthorization(true);
        return $this;
    }
    /**
     * Check for the response to be valid.
     * @param Payload\Payment\IConfirmFundsReply $response
     * @return self
     */
    protected function _validateConfirmFundsResponse(Payload\Payment\IConfirmFundsReply $response, $payment)
    {
        // if auth was a complete success, accept the response and move on
        if ($response->isSuccess()) {
            return $this;
        }
        // if auth was a complete success, accept the response and move on
        if ($response->isTimeout()) {
            $errorMessage = $this->helper->__(self::RADIAL_PAYPAL_CONFIRM_FUNDS_TIMEOUT);
            $this->_failPaymentRequest($errorMessage);
            return $this;
        }
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        $errorMessage = $this->helper->__(self::RADIAL_PAYPAL_DENIED_MESSAGE);
        $this->paymentsHelper->failConfirmFundsRequest($order, $errorMessage);
        $this->_failPaymentRequest($errorMessage);
        return $this;
    }
    /**
     * Fail the auth request by setting a checkout step to return to and throwing
     * an exception.
     * @see self::_setCheckoutStep for available checkout steps to return to
     * @param string $errorMessage
     * @throws Radial_CreditCard_Exception Always
     */
    protected function _failPaymentRequest($errorMessage)
    {
        throw Mage::exception('Radial_PayPal', $this->helper->__($errorMessage));
    }

    /**
     * Set Express Checkout Request/ Response
     *
     * @param  string
     * @param  string
     * @param  Mage_Sales_Model_Quote
     * @return array
     *
     * @throws Radial_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function setExpressCheckout($returnUrl, $cancelUrl, Mage_Sales_Model_Quote $quote)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationSetExpressCheckout
        );

        $payload = $sdk->getRequestBody();
        $payload->setOrderId($quote->reserveOrderId()->getReservedOrderId())
            ->setReturnUrl($returnUrl)
            ->setCancelUrl($cancelUrl)
            ->setLocaleCode(Mage::app()->getLocale()->getDefaultLocale())
            ->setAmount($this->getTotal('grand_total', $quote))
            ->setCurrencyCode($quote->getQuoteCurrencyCode());

        $useAddressOverride = $this->useAddressOverride($quote);
        $payload->setAddressOverride($useAddressOverride);
        if ($useAddressOverride) {
            $this->addShippingAddress($quote->getShippingAddress(), $payload);
        }

        $this->addLineItems($quote, $payload);

        Mage::dispatchEvent('radial_paypal_set_express_checkout_before_send', ['payload' => $payload, 'quote' => $quote]);
        $sdk->setRequestBody($payload);
        $reply = $this->sendRequest($sdk);
        Mage::dispatchEvent('radial_paypal_set_express_checkout_after_send', ['payload' => $reply, 'quote' => $quote]);

        if (!$reply->isSuccess() || is_null($reply->getToken())) {
            // Only set and do express have the error message in the reply.
            $logMessage =
                'SetExpressCheckout request failed with message ({reply_message}). See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(
                __CLASS__,
                ['reply_message' => $reply->getErrorMessage()]
            ));
            $e = Mage::exception('Radial_PayPal', $this->helper->__(self::RADIAL_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        return [
            'method' => Radial_PayPal_Model_Method_Express::CODE,
            'token'  => $reply->getToken()
        ];
    }

    /**
     * Get Express Checkout Request/ Response
     *
     * @param  Mage_Sales_Model_Quote
     * @param  string                 $token as from setExpressCheckout
     * @param  string
     * @return array
     *
     * @throws Radial_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function getExpressCheckout($orderId, $token, $currencyCode)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationGetExpressCheckout
        );
        $payload = $sdk->getRequestBody();
        $payload->setOrderId($orderId)
            ->setToken($token)
            ->setCurrencyCode($currencyCode);
        Mage::dispatchEvent('radial_paypal_get_express_checkout_before_send', ['payload' => $payload]);
        $sdk->setRequestBody($payload);
        $reply = $this->sendRequest($sdk);
        Mage::dispatchEvent('radial_paypal_get_express_checkout_after_send', ['payload' => $reply]);
        if (!$reply->isSuccess()) {
            $logMessage = 'PayPal request failed. See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $e = Mage::exception('Radial_PayPal', $this->helper->__(self::RADIAL_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        return [
            'method'           => Radial_PayPal_Model_Method_Express::CODE,
            'order_id'         => $reply->getOrderId(),
            'country_id'       => $reply->getPayerCountry(),
            'email'            => $reply->getPayerEmail(),
            'firstname'        => $reply->getPayerFirstName(),
            'payer_id'         => $reply->getPayerId(),
            'lastname'         => $reply->getPayerLastName(),
            'middlename'       => $reply->getPayerMiddleName(),
            'suffix'           => $reply->getPayerNameHonorific(),
            'phone'            => $reply->getPayerPhone(),
            'status'           => $reply->getPayerStatus(),
            'response_code'    => $reply->getResponseCode(),
            'billing_address'  => [
                'street'      => stripcslashes($reply->getBillingLines()),
                'city'        => $reply->getBillingCity(),
                'region_code' => $reply->getBillingMainDivision(),
                'postcode'    => $reply->getBillingPostalCode(),
                'country_id'  => $reply->getBillingCountryCode(),
                'status'      => $reply->getBillingAddressStatus(),
            ],
            'shipping_address' => [
                'street'      => stripcslashes($reply->getShipToLines()),
                'city'        => $reply->getShipToCity(),
                'region_code' => $reply->getShipToMainDivision(),
                'postcode'    => $reply->getShipToPostalCode(),
                'country_id'  => $reply->getShipToCountryCode(),
                'status'      => $reply->getShippingAddressStatus(),
            ],
        ];
    }

    /**
     * Do Express Checkout Request/ Response
     *
     * @param  Mage_Sales_Model_Quote
     * @param  string $token as from setExpressCheckout
     * @param  string $payerId as from getExpressCheckout or from a PayPal redirected URL
     * @param  string $pickUpStoreId as from getExpressCheckout or from a PayPal redirected URL (optional)
     * @return array
     *
     * @throws Radial_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function doExpressCheckout(Mage_Sales_Model_Quote $quote, $token, $payerId, $pickUpStoreId = null)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoExpressCheckout
        );
        $payload = $sdk->getRequestBody();
        $payload->setRequestId(
            $this->coreHelper->generateRequestId(
                self::PAYPAL_DOEXPRESS_REQUEST_ID_PREFIX
            )
        )
            ->setOrderId($quote->reserveOrderId()->getReservedOrderId())
            ->setToken($token)
            ->setPayerId($payerId)
            ->setCurrencyCode($quote->getQuoteCurrencyCode())
            ->setAmount($this->getTotal('grand_total', $quote));
        /** @var Mage_Sales_Model_Quote_Address $shippingAddress */
        $shippingAddress = $quote->getIsVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $this->addShippingAddress($shippingAddress, $payload);
        if ($pickUpStoreId) {
            $payload->setPickUpStoreId($pickUpStoreId);
        }
        $this->addLineItems($quote, $payload);
        Mage::dispatchEvent('radial_paypal_do_express_checkout_before_send', ['payload' => $payload, 'quote' => $quote]);
        $sdk->setRequestBody($payload);
        $reply = $this->sendRequest($sdk);
        Mage::dispatchEvent('radial_paypal_do_express_checkout_after_send', ['payload' => $reply, 'quote' => $quote]);
        if (!$reply->isSuccess()) {
            $logData = ['error_message' => $reply->getErrorMessage()];
            $logMessage = 'PayPal request failed with message "{error_message}". See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__, $logData));
            $e = Mage::exception('Radial_PayPal', $this->helper->__(static::RADIAL_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        // save the token for gateway operations
        $payment = $quote->getPayment();
        $payment->setCcNumberEnc($payment->encrypt($reply->getTransactionId()));
        return [
            'method'          => Radial_PayPal_Model_Method_Express::CODE,
            'order_id'        => $reply->getOrderId(),
            'transaction_id'  => $reply->getTransactionId(),
            'response_code'   => $reply->getResponseCode(),
            'auth_request_id' => $payload->getRequestId(),
        ];
    }

    /**
     * Do Authorization Request/ Response
     *
     * @param  Mage_Sales_Model_Quote
     * @return array
     *
     * @throws Radial_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function doAuthorization(Mage_Sales_Model_Quote $quote)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoAuthorization
        );
        $payload = $sdk->getRequestBody();
        $payload->setRequestId(
            $this->coreHelper->generateRequestId(
                self::PAYPAL_DOAUTHORIZATION_REQUEST_ID_PREFIX
            )
        )
            ->setOrderId($quote->reserveOrderId()->getReservedOrderId())
            ->setCurrencyCode($quote->getQuoteCurrencyCode())
            ->setAmount($this->getTotal('grand_total', $quote));
        Mage::dispatchEvent('radial_paypal_do_authorization_before_send', ['payload' => $payload, 'quote' => $quote]);
        $sdk->setRequestBody($payload);
        $reply = $this->sendRequest($sdk);
        Mage::dispatchEvent('radial_paypal_do_authorization_after_send', ['payload' => $reply, 'quote' => $quote]);
        $isSuccess = $reply->isSuccess();
        if (!$isSuccess) {
            $logMessage = 'PayPal request failed.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $e = Mage::exception('Radial_PayPal', $this->helper->__(static::RADIAL_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        return [
            'method'         => Radial_PayPal_Model_Method_Express::CODE,
            'order_id'       => $reply->getOrderId(),
            'payment_status' => $reply->getPaymentStatus(),
            'pending_reason' => $reply->getPendingReason(),
            'reason_code'    => $reply->getReasonCode(),
            'is_authorized'  => $isSuccess,
        ];
    }

    /**
     * Void the payment auth made for a quote.
     *
     * @param Mage_Sales_Model_Quote
     * @return array
     * @throws Radial_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function doVoidQuote(Mage_Sales_Model_Quote $quote)
    {
        return $this->doVoid($quote->reserveOrderId()->getReservedOrderId(), $quote->getQuoteCurrencyCode());
    }

    /**
     * Do Void Request/Response
     *
     * @param  Mage_Sales_Model_Order
     * @return array
     * @throws Radial_PayPal_Exception when the operation cannot be completed or fails.
     */
    public function doVoidOrder(Mage_Sales_Model_Order $order)
    {
        return $this->doVoid($order->getIncrementId(), $order->getOrderCurrencyCode());
    }

    /**
     * Make a PayPal void request for the order identified by the order id.
     *
     * @param string
     * @param string
     * @return array
     * @throws Radial_PayPal_Exception when the operation cannot be completed or fails.
     */
    protected function doVoid($orderId, $currencyCode)
    {
        $sdk = $this->getSdk(
            $this->config->apiOperationDoVoid
        );
        $payload = $sdk->getRequestBody();
        $payload->setOrderId($orderId)
            ->setRequestId($this->coreHelper->generateRequestId(self::PAYPAL_DOVOID_REQUEST_ID_PREFIX))
            ->setCurrencyCode($currencyCode);
        Mage::dispatchEvent('radial_paypal_do_void_before_send', ['payload' => $payload]);
        $sdk->setRequestBody($payload);
        $reply = $this->sendRequest($sdk);
        Mage::dispatchEvent('radial_paypal_do_void_after_send', ['payload' => $reply]);
        $isVoided = $reply->isSuccess();
        if (!$reply->isSuccess()) {
            $logMessage = 'PayPal DoVoid failed. See exception log for details.';
            $this->logger->warning($logMessage, $this->logContext->getMetaData(__CLASS__));
            $e = Mage::exception('Radial_PayPal', $this->helper->__(static::RADIAL_PAYPAL_API_FAILED));
            $this->logger->logException($e, $this->logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        return [
            'method'    => Radial_PayPal_Model_Method_Express::CODE,
            'order_id'  => $reply->getOrderId(),
            'is_voided' => $isVoided
        ];
    }

    /**
     * Check if a quote should use an address override - already has a shipping
     * address that is valid for PayPal.
     *
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    protected function useAddressOverride(Mage_Sales_Model_Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        return !is_null($shippingAddress->getId())
            && $shippingAddress->getStreet()
            && $shippingAddress->getCity()
            && $shippingAddress->getCountryId();
    }

    /**
     * Add an address to a payload
     *
     * @param  Mage_Sales_Model_Address_Abstract
     * @param  IShippingAddress
     * @return self
     */
    protected function addShippingAddress(Mage_Sales_Model_Quote_Address $shippingAddress, IShippingAddress $payload)
    {
        $payload->setShipToLines(implode('\n', $shippingAddress->getStreet()));
        $payload->setShipToCity($shippingAddress->getCity());
        $payload->setShipToMainDivision($shippingAddress->getRegionCode());
        $payload->setShipToCountryCode($shippingAddress->getCountryId());
        $payload->setShipToPostalCode($shippingAddress->getPostcode());
        return $this;
    }

    /**
     * Send the request via the sdk
     *
     * @param  IBidirectionalApi
     * @return Payload
     *
     * @throws Radial_PayPal_Exception
     * @throws UnsupportedOperation
     * @throws UnsupportedHttpAction
     * @throws Exception
     */
    protected function sendRequest(IBidirectionalApi $sdk)
    {
        $logger = $this->logger;
        $logContext = $this->logContext;
        try {
            $sdk->send();
            $reply = $sdk->getResponseBody();
            return $reply;
        } catch (InvalidPayload $e) {
            $logMessage = 'Invalid payload for PayPal request. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
        } catch (NetworkError $e) {
            $logMessage = 'Caught network error sending the PayPal request. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
        } catch (UnsupportedOperation $e) {
            $logMessage = 'The PayPal operation is unsupported in the current configuration. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        } catch (UnsupportedHttpAction $e) {
            $logMessage = 'The PayPal operation is configured with an unsupported HTTP action. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        } catch (Exception $e) {
            $logMessage = 'Encountered unexpected exception from PayPal operation. See exception log for more details.';
            $logger->warning($logMessage, $logContext->getMetaData(__CLASS__, ['exception_message' => $e->getMessage()]));
            $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
            throw $e;
        }
        $e = Mage::exception('Radial_PayPal', $this->helper->__(static::RADIAL_PAYPAL_API_FAILED));
        $logger->logException($e, $logContext->getMetaData(__CLASS__, [], $e));
        throw $e;
    }

    /**
     * Get the API SDK for the operation.
     *
     * @param  Varien_Object
     * @return IBidirectionalApi
     */
    protected function getSdk($operation, array $endpointParams = [])
    {
        return $this->coreHelper->getSdkApi($this->config->apiService, $operation, $endpointParams);
    }

    /**
     * Generate ILineItem objects for each item and add to the container payload.
     *
     * @param Mage_Sales_Model_Quote
     * @param ILineItemContainer
     */
    protected function addLineItems(Mage_Sales_Model_Quote $quote, ILineItemContainer $container)
    {
        if ($this->canIncludeLineItems($quote)) {
            $this->processLineItems($quote, $container->getLineItems())
                ->processNegativeLineItems($quote, $container->getLineItems())
		->processGiftWrapLineItems($quote, $container->getLineItems());
            $container->calculateLineItemsTotal();
            $container->setShippingTotal($this->getTotal('shipping', $quote));
            
	    if( isset($quote->getTotals()['radial_tax']) && $quote->getTotals()['radial_tax']->getValue())
            {
                $container->setTaxTotal($this->getTotal('radial_tax', $quote));
            } else {
                if( isset($quote->getTotals()['tax']) && $quote->getTotals()['tax']->getValue())
                {
                        $container->setTaxTotal($quote->getTotals()['tax']->getValue());
                } else {
                        $container->setTaxTotal(0);
                }
	    }

	    $container->setCurrencyCode($quote->getQuoteCurrencyCode());
        }
    }

    /**
     * process gift wrap
     *
     * @param  Mage_Sales_Model_Quote
     * @param  ILineItemIterable
     * @return self
     */
    protected function processGiftWrapLineItems(Mage_Sales_Model_Quote $quote, ILineItemIterable $lineItems )
    {
        $currencyCode = $quote->getQuoteCurrencyCode();

        foreach( $quote->getAllItems() as $quoteItem )
        {
                if( $quoteItem->getGwId() && $quoteItem->getGwPrice() )
                {
                        $giftWrap = Mage::getModel('enterprise_giftwrapping/wrapping')->load($quoteItem->getGwId());
                        if ($giftWrap->getId()) {
                                $giftQty = $quoteItem->getQty() ?: 1;

                                $lineItem = $lineItems->getEmptyLineItem();
                                $lineItem->setName($giftWrap->getDesign())
                                         ->setSequenceNumber($giftWrap->getGwId())
                                         ->setQuantity(1)
                                         ->setUnitAmount($quoteItem->getGwPrice() * $giftQty)
                                         ->setCurrencyCode($currencyCode);
                                $lineItems->offsetSet($lineItem, null);
                        }
                }
        }

        if( $quote->getGwId() && $quote->getGwPrice() )
        {
                 $giftWrap = Mage::getModel('enterprise_giftwrapping/wrapping')->load($quote->getGwId());
                 if ($giftWrap->getId()) {
                        $lineItem = $lineItems->getEmptyLineItem();
                        $lineItem->setName($giftWrap->getDesign())
                                 ->setSequenceNumber($giftWrap->getGwId())
                                 ->setQuantity(1)
                                 ->setUnitAmount($giftWrap->getBasePrice())
                                 ->setCurrencyCode($currencyCode);
                        $lineItems->offsetSet($lineItem, null);
                 }
        }
    }

    /**
     * recursively process line items into payloads
     *
     * @param  Mage_Sales_Model_Quote
     * @param  ILineItemIterable
     * @param  string
     * @return self
     */
    protected function processLineItems(Mage_Sales_Model_Quote $quote, ILineItemIterable $lineItems)
    {
        $items = $this->selectionHelper->selectFrom($quote->getAllItems());
        foreach ($items as $item) {
            $this->createLineItem($item, $lineItems, $quote->getQuoteCurrencyCode());
        }
        return $this;
    }

    /**
     * process specific amount types into negative-value line item
     * payloads
     *
     * @param Mage_Sales_Model_Quote
     * @param ILineItemIterable
     * @return self
     */
    protected function processNegativeLineItems(Mage_Sales_Model_Quote $quote, ILineItemIterable $lineItems)
    {
        $negativeAmountTypes = array('discount', 'giftcardaccount', 'ebayenterprise_giftcard');
        $currencyCode = $quote->getQuoteCurrencyCode();
        foreach ($negativeAmountTypes as $totalType) {
            $totalAmount = $this->getTotal($totalType, $quote);
            if ($totalAmount) {
                // ensure all amounts are negative
                $totalAmount = -abs($totalAmount);
                $lineItem = $lineItems->getEmptyLineItem();
                $lineItem->setName($this->helper->__($totalType))
                    ->setSequenceNumber($totalType)
                    ->setQuantity(1)
                    ->setUnitAmount($totalAmount)
                    ->setCurrencyCode($currencyCode);
                $lineItems->offsetSet($lineItem, null);
            }
        }
        return $this;
    }

    /**
     * return true if the line items can be included in the message
     * @param  Mage_Sales_Model_Quote
     * @return bool
     */
    protected function canIncludeLineItems($quote)
    {
        $reductions = -$this->getTotal('discount', $quote);
        $reductions += $this->getTotal('giftcardaccount', $quote);
        $reductions += $this->getTotal('ebayenterprise_giftcard', $quote);
        // due to the way paypal verifies line items total and the need to send
        // discount/giftcard (admustment) amounts as negative line items, the LineItemsTotal
        // will not match what paypal is expecting when the adustment amounts add up to more
        // than the total amount for the line items.
        return $this->config->transferLines &&
            $this->getTotal('subtotal', $quote) >= $reductions;
    }

    /**
     * build out an ILineItem payload and add it to the ILineItemIterable.
     *
     * @param  Mage_Sales_Model_Quote_Item_Abstract
     * @param  ILineItemIterable
     * @param  string
     * @return self
     */
    public function createLineItem(
        Mage_Sales_Model_Quote_Item_Abstract $item,
        ILineItemIterable $lineItems,
        $currencyCode
    ) {
        $lineItem = $lineItems->getEmptyLineItem();
        $lineItem->setName($this->helper->__($item->getProduct()->getName()))
            ->setSequenceNumber($item->getId())
            ->setQuantity($item->getTotalQty())
            ->setCurrencyCode($currencyCode);
        if ($this->canIncludeAmounts($item)) {
            $lineItem->setUnitAmount($item->getPrice());
        }
        $lineItems->offsetSet($lineItem, null);
    }

    /**
     * determine if the item's amounts should be put into the request.
     *
     * @param Mage_Sales_Model_Quote_Item_Abstract
     * @return bool
     */
    protected function canIncludeAmounts(Mage_Sales_Model_Quote_Item_Abstract $item)
    {
        return !(
            // only the parent item will have the bundle product type
            $item->getProduct()->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
            && $item->isChildrenCalculated()
        );
    }

    /**
     * Get the specified total amount for the quote.
     *
     * @param  string
     * @param  Mage_Sales_Model_Quote
     * @return float
     */
    protected function getTotal($type, Mage_Sales_Model_Quote $quote)
    {
        $totals = $quote->getTotals();
        return isset($totals[$type]) ? $totals[$type]->getValue() : 0.0;
    }
}
