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

class EbayEnterprise_PayPal_Model_Multishipping
{
    const PAYPAL_START_URI_PATH = 'paypal-express/checkout/start';

    /** @var EbayEnterprise_Paypal_Model_Express_Api */
    protected $api;
    /** @var Mage_Checkout_Model_Session */
    protected $checkoutSession;
    /** @var string */
    protected $paypalStartUrl;

    /**
     * @param array $initParams May have this key:
     *                          - 'api' => EbayEnterprise_Paypal_Model_Express_Api
     */
    public function __construct(array $initParams=[])
    {
        list($this->api) = $this->checkTypes(
            $this->nullCoalesce($initParams, 'api', Mage::getModel('ebayenterprise_paypal/express_api'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param  EbayEnterprise_Paypal_Model_Express_Api
     * @return array
     */
    protected function checkTypes(EbayEnterprise_Paypal_Model_Express_Api $api)
    {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
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

    /**
     * Get a singleton instance of the checkout/session class.
     *
     * @return Mage_Checkout_Model_Session
     * @codeCoverageIgnore
     */
    protected function getCheckoutSession()
    {
        if (!$this->checkoutSession) {
            $this->checkoutSession = Mage::getSingleton('checkout/session');
        }
        return $this->checkoutSession;
    }

    /**
     * Get the PayPal Express startup URL.
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function getPayPalExpressStartupUrl()
    {
        if (!$this->paypalStartUrl) {
            $this->paypalStartUrl = Mage::getUrl(static::PAYPAL_START_URI_PATH);
        }
        return $this->paypalStartUrl;
    }

    /**
     * Redirect customer to PayPal payment page if the customer choose to pay with
     * EbayEnterprise PayPal payment method. If the customer is returning back from paying
     * with PayPal, then allow the customer to continue to go to the next step of the Multi-shipping checkout.
     *
     * @param  Mage_Checkout_MultishippingController
     * @return self
     */
    public function initializePaypalExpressCheckout(Mage_Checkout_MultishippingController $controllerAction)
    {
        /** @var Mage_Checkout_Model_Session */
        $checkoutSession = $this->getCheckoutSession();
        /** @var Mage_Core_Controller_Request_Http */
        $request = $controllerAction->getRequest();
        /** @var array | null */
        $paymentData = $checkoutSession->getMultiShippingPaymentData() ?: (array) $request->getParam('payment');
        if ($this->isRedirectToPayPal($paymentData)) {
            $checkoutSession->setIsUseMultiShippingCheckout(true)
                ->setMultiShippingPaymentData($paymentData);
            /** @var Mage_Core_Controller_Response_Http */
            $response = $controllerAction->getResponse();
            $response->setRedirect($this->getPayPalExpressStartupUrl());
            return $this;
        }
        /** @var Mage_Sales_Model_Quote */
        $quote = $checkoutSession->getQuote();
        $quote->setIsMultiShipping(true);
        // Remove Payment Data from session once we are passed returning
        // from PayPal Express page.
        $checkoutSession->setMultiShippingPaymentData(null);
        $request->setPost('payment', $paymentData);
        return $this;
    }

    /**
     * Determine if it is possible to redirect to PayPal page.
     *
     * @param  array
     * @return bool
     */
    protected function isRedirectToPayPal(array $paymentData)
    {
        /** @var string | null */
        $paymentMethod = null;
        /** @var bool */
        $isReturnFromPaypal = false;
        if (!empty($paymentData)) {
            $paymentMethod = array_key_exists('method', $paymentData) ? $paymentData['method'] : null;
            $isReturnFromPaypal = array_key_exists('is_return_from_paypal', $paymentData)
                ? (bool) $paymentData['is_return_from_paypal'] : false;
        }
        return $paymentMethod === EbayEnterprise_PayPal_Model_Method_Express::CODE
            && !$isReturnFromPaypal;
    }

    /**
     * Send do express PayPal checkout request and do authorize PayPal request for the given
     * multi-shipping quote and import the reply data to the quote payment.
     *
     * @param  Mage_Sales_Model_Quote
     * @return self
     */
    public function processPaypalExpressPayment(Mage_Sales_Model_Quote $quote)
    {
        /** @var Mage_Sales_Model_Quote_Payment */
        $payment = $quote->getPayment();
        /** @var Varien_Object */
        $paypalData = new Varien_Object($payment->getAdditionalInformation());
        /** @var string */
        $token = $paypalData->getPaypalExpressCheckoutToken();
        /** @var string */
        $payerId = $paypalData->getPaypalExpressCheckoutPayerId();
        /** @var string */
        $paymentMethod = $payment->getMethod();
        if ($quote->getIsMultiShipping() && $paymentMethod === EbayEnterprise_PayPal_Model_Method_Express::CODE) {
            // Collecting totals in order to get eBay Enterprise Tax total for this quote
            $quote->collectTotals();
            /** @var array */
            $data = array_merge(
                $this->api->doExpressCheckout($quote, $token, $payerId),
                $this->api->doAuthorization($quote)
            );
            $payment->importData($data);
        }
        return $this;
    }
}
