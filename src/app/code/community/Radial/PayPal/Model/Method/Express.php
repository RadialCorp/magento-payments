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

use eBayEnterprise\RetailOrderManagement\Payload\Payment;

/**
 * Payment Method for PayPal payments through Retail Order Management.
 * @SuppressWarnings(TooManyFields)
 */
class Radial_PayPal_Model_Method_Express extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'radial_paypal_express';

    const PAYMENT_INFO_AUTH_REQUEST_ID = 'auth_request_id';
    const SETTLEMENT_FAILED_MESSAGE = 'RADIAL_PAYPAL_SETTLEMENT_FAILED';

    protected $_code = self::CODE; // compatibility with mage payment method expectations
    protected $_formBlockType = 'radial_paypal/express_form';
    protected $_infoBlockType = 'radial_paypal/express_payment_info';

    /**
     * Mage Payment Method Availability options
     */
    protected $_isGateway = true;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canFetchTransactionInfo = true;
    protected $_canCreateBillingAgreement = false;
    protected $_canReviewPayment = true;

    /** @var bool */
    protected $_isUsingClientSideEncryption;
    /** @var Radial_PayPal_Helper_Data */
    protected $_helper;
    /** @var Radial_Core_Model_Config_Registry */
    protected $_config;
    /** @var Radial_Paypal_Model_Express_Api */
    protected $_api;
    /** @var string[] */
    protected $_selectorKeys = array(
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_TOKEN,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_SHIPPING_OVERRIDDEN,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_SHIPPING_METHOD,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_PAYER_ID,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_REDIRECT,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_BILLING_AGREEMENT,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_BUTTON,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_AUTHORIZED_FLAG,
        Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_VOIDED_FLAG,
        self::PAYMENT_INFO_AUTH_REQUEST_ID,
    );

    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;

    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;

    /**
     * `__construct` overridden in Mage_Payment_Model_Method_Abstract as a no-op.
     * Override __construct here as the usual protected `_construct` is not called.
     *
     * @param array $initParams May contain:
     *                          -  'helper' => Radial_PayPal_Helper_Data
     *                          -  'core_helper' => Radial_Core_Helper_Data
     *                          -  'config' => Radial_Core_Model_Config_Registry
     *                          -  'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          -  'context' => EbayEnterprise_MageLog_Helper_Context
     *                          -  'api' => Radial_Paypal_Model_Express_Api
     */
    public function __construct(array $initParams = array())
    {
        list($this->_helper, $this->_coreHelper, $this->_logger, $this->_context,$this->_config, $this->_api)
            = $this->_checkTypes(
                $this->_nullCoalesce(
                    $initParams,
                    'helper',
                    Mage::helper('radial_paypal')
                ),
                $this->_nullCoalesce(
                    $initParams,
                    'core_helper',
                    Mage::helper('radial_core')
                ),
                $this->_nullCoalesce(
                    $initParams,
                    'logger',
                    Mage::helper('ebayenterprise_magelog')
                ),
                $this->_nullCoalesce(
                    $initParams,
                    'context',
                    Mage::helper('ebayenterprise_magelog/context')
                ),
                $this->_nullCoalesce(
                    $initParams,
                    'config',
                    Mage::helper('radial_paypal')->getConfigModel()
                ),
                $this->_nullCoalesce(
                    $initParams,
                    'api',
                    Mage::getModel('radial_paypal/express_api')
                )
            );
    }

    /**
     * Type hinting for self::__construct $initParams
     *
     * @param Radial_PayPal_Helper_Data
     * @param Radial_Core_Helper_Data
     * @param Mage_Core_Helper_Http
     * @param EbayEnterprise_MageLog_Helper_Data
     * @param EbayEnterprise_MageLog_Helper_Context
     * @param Radial_Core_Model_Config_Registry
     * @param Radial_Paypal_Model_Express_Api
     *
     * @return array
     */
    protected function _checkTypes(
        Radial_PayPal_Helper_Data $helper,
        Radial_Core_Helper_Data $coreHelper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context,
        Radial_Core_Model_Config_Registry $config,
        Radial_Paypal_Model_Express_Api $api
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the
     * default value.
     *
     * @param  array      $arr
     * @param  string|int $field Valid array key
     * @param  mixed      $default
     *
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * Invoice has been created.
     * @param Mage_Sales_Model_Order_Creditmemo
     * @param Mage_Sales_Model_Order_Payment
     * @return self
     * @throws Mage_Core_Exception
     */
    public function processCreditmemo($creditmemo, $payment)
    {
        // The creditmemo must be saved before continuing.
        $creditmemo->save();
        parent::processCreditmemo($creditmemo, $payment);
        try {
            $this->_api->doRefund($creditmemo, $payment);
        } catch (Exception $e) {
            // settlement must be allowed to fail
            // set creditmemo status as OPEN to trigger a retry and notify admin
            $creditmemo->setState(Mage_Sales_Model_Order_Creditmemo::STATE_OPEN);

	    $retry = $creditmemo->getDeliveryStatus();
            $retryN = $retry + 1;
            $creditmemo->setDeliveryStatus($retryN);
            $creditmemo->save();

            $errorMessage = $this->_helper->__(self::SETTLEMENT_FAILED_MESSAGE);
            $this->getSession()->addNotice($errorMessage);
            $this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
        }
        return $this;
    }

    /**
     * Invoice has been created.
     * @param Mage_Sales_Model_Order_Invoice
     * @param Mage_Sales_Model_Order_Payment
     * @return self
     * @throws Mage_Core_Exception
     */
    public function processInvoice($invoice, $payment)
    {
        // The invoice must be saved before continuing.
        $invoice->save();
        parent::processInvoice($invoice, $payment);
        try {
            $this->_api->doCapture($invoice, $payment);
        } catch (Exception $e) {
            // settlement must be allowed to fail
            // set invoice status as OPEN to trigger a  retry and notify admin
            $invoice->setIsPaid(false);

	    $retry = $invoice->getDeliveryStatus();
            $retryN = $retry + 1;
            $invoice->setDeliveryStatus($retryN);
            $invoice->save();

            $errorMessage = $this->_helper->__(self::SETTLEMENT_FAILED_MESSAGE);
            $this->getSession()->addNotice($errorMessage);
            $this->_logger->logException($e, $this->_context->getMetaData(__CLASS__, [], $e));
        }
        return $this;
    }

    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->canCapture()) {
            Mage::throwException(Mage::helper('payment')->__('Capture action is not available.'));
        }

	$order = $payment->getOrder();
        $notCapturable = 1;

        if ($order->hasInvoices()) {
          $oInvoiceCollection = $order->getInvoiceCollection();
          foreach ($oInvoiceCollection as $oInvoice) {
                if( $oInvoice->getRequestedCaptureCase() != Mage_Sales_Model_Order_Invoice::NOT_CAPTURE && $oInvoice->getState() != Mage_Sales_Model_Order_Invoice::STATE_CANCELED && $oInvoice->getState() != Mage_Sales_Model_Order_Invoice::STATE_PAID )
                {
                        $notCapturable = 0;
                        break;
                }
          }

          if( $notCapturable )
          {
                return $this;
          }
        }

        // confirm funds
        $this->_api->doConfirm($payment, $amount);
        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        if (!$this->canRefund()) {
            Mage::throwException(Mage::helper('payment')->__('Refund action is not available.'));
        }
        return $this;
    }

    /**
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        if (!$this->canVoid($payment)) {
            Mage::throwException(Mage::helper('payment')->__('Void action is not available.'));
        }
        $this->_api->doVoidOrder($payment->getOrder());
        return $this;
    }

    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
	parent::cancel($payment);
        $this->_api->doVoidOrder($payment->getOrder());
        return $this;
    }

    /**
     * Return true if the payment can be voided.
     *
     * @param  Varien_Object $payment
     *
     * @return bool
     */
    public function canVoid(Varien_Object $payment)
    {
        if ($payment instanceof Mage_Sales_Model_Order_Invoice
            || $payment instanceof Mage_Sales_Model_Order_Creditmemo
        ) {
            return false;
        }
        $info = $this->getInfoInstance();
        return $this->_canVoid
            && $info->getAdditionalInformation(
                Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_AUTHORIZED_FLAG
            )
            && !$info->getAdditionalInformation(
                Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_IS_VOIDED_FLAG
            );
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see Mage_Checkout_OnepageController::savePaymentAction()
     * @see Mage_Sales_Model_Quote_Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return Mage::getUrl('radial_paypal_express/checkout/start');
    }

    /**
     * Set the scope of the payment method to a mage store.
     *
     * @param mixed $storeId
     *
     * @return self
     */
    public function setStore($storeId = null)
    {
        $this->_config->setStore($storeId);
        return $this;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param mixed  $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        return Mage::helper('radial_paypal')->getConfigModel()
            ->setStore($storeId)
            ->getConfig($field);
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     *
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        $result = parent::assignData($data);
        if ($data instanceof Varien_Object) {
            $data = $data->getData();
        }
        if (is_array($data)) {
            // array keys for the fields to store into the payment info object.
            $filteredData = array_intersect_key($data, array_flip($this->_selectorKeys));
            $info = $this->getInfoInstance();
            foreach ($filteredData as $key => $value) {
                $info->setAdditionalInformation(
                    $key,
                    $value
                );
            }
            if (isset($data['shipping_address']['status'])) {
                $this->getInfoInstance()->setAdditionalInformation(
                    Radial_PayPal_Model_Express_Checkout::PAYMENT_INFO_ADDRESS_STATUS,
                    $data['shipping_address']['status']
                );
            }
        }
        return $result;
    }

    /**
     * Retrieve adminhtml session model object
     * @return Mage_Adminhtml_Model_Session
     */
    protected function getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
}
