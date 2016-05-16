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
 * Paypal expess checkout shortcut link
 *
 * @method string getShortcutHtmlId()
 * @method string getImageUrl()
 * @method string getCheckoutUrl()
 * @method string getConfirmationUrl()
 * @method string getIsOnCatalogProductPage()
 * @method string getConfirmationMessage()
 * @method bool   getIsQuoteAllowed()
 */
class EbayEnterprise_PayPal_Block_Express_Shortcut extends Mage_Core_Block_Template
{
    const VISIBLE_PRODUCT = 'visible_on_product';
    const VISIBLE_CART = 'visible_on_cart';
    const HTML_ID_PREFIX = 'ebayenterprise_paypal_shortcut_';
    const DEFAULT_IMAGE_URL = 'https://www.paypal.com/{locale_code}/i/btn/btn_xpressCheckout.gif';
    /**
     * Position of "OR" label against shortcut
     */
    const POSITION_BEFORE = 'before';
    const POSITION_AFTER = 'after';

    protected $_paymentMethodCode = 'ebayenterprise_paypal_express';
    /**
     * Start express action
     *
     * @var string
     */
    protected $_startAction =
        'ebayenterprise_paypal_express/checkout/start/button/1';
    /**
     * Express checkout model factory name
     *
     * @var string
     */
    protected $_checkoutType = 'ebayenterprise_paypal/express_checkout';

    /** @var EbayEnterprise_PayPal_Helper_Data */
    protected $_helper;
    protected $_config;
    /** @var Mage_Checkout_Model_Session */
    protected $_checkoutSession;
    /** @var Mage_Sales_Model_Quote */
    protected $_quote;
    /** @var string */
    protected $_localeCode;
    /** @var Mage_Core_Helper_Data */
    protected $_coreHelper;
    /** @var string */
    protected $_payPalImageUrl;
    /** @var string */
    protected $_htmlId;
    /** @var Mage_Payment_Helper_Data */
    protected $_paymentHelper;

    /**
     * Set up the paypal helper.
     */
    public function _construct()
    {
        parent::_construct();

        $app = Mage::app();
        $locale = $app->getLocale();

        $this->_coreHelper = $this->helper('core');
        $this->_paymentHelper = Mage::helper('payment');
        $this->_helper = Mage::helper('ebayenterprise_paypal');
        $this->_config = $this->_helper->getConfigModel();
        $this->_localeCode = $locale->getLocaleCode();
        $url =  $this->_config->shortcutExpressCheckoutButton ?: self::DEFAULT_IMAGE_URL;
        $this->_payPalImageUrl = str_replace(array('{locale_code}'), array($this->_localeCode), $url);
        $this->_htmlId = $this->_coreHelper->uniqHash(self::HTML_ID_PREFIX);
    }

    protected function _getConfigFlag($key)
    {
        return $this->_config->getConfigFlag($key);
    }

    /**
     * Check if we should render based on product page config.
     *
     * @return bool
     */
    protected function _shouldRenderProductPage()
    {
        return !($this->getIsOnCatalogProductPage() && !$this->_getConfigFlag(self::VISIBLE_PRODUCT));
    }

    /**
     * Show shortcut on product page if nonzero price.
     *
     * @return bool
     */
    protected function _isPositiveProductPrice()
    {
        $currentProduct = $this->_getCurrentProduct();
        if ($currentProduct) {
            $productPrice = (float) $currentProduct->getFinalPrice();
            if (empty($productPrice) && !$currentProduct->isGrouped()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if we should render based on cart page config.
     *
     * @return bool
     */
    protected function _shouldRenderCartPage()
    {
        return !(!$this->getIsOnCatalogProductPage() && !$this->_getConfigFlag(self::VISIBLE_CART));
    }

    /**
     * Check if we should render based on quote validation.
     *
     * @return bool
     */
    protected function _shouldRenderQuote()
    {
        $quote = $this->_getQuote();

        // validate minimum quote amount and validate quote for zero grandtotal
        if (null !== $quote && $this->_hasZeroGrandTotal($quote)) {
            return false;
        }
        // check payment method availability
        $methodInstance = $this->_paymentHelper->getMethodInstance(
            $this->_paymentMethodCode
        );
        if (!$methodInstance || !$methodInstance->isAvailable($quote)) {
            return false;
        }
        return true;
    }
    /**
     * Check if the quote has zero grandtotal.
     * @param  Mage_Sales_Model_Quote $quote
     * @return bool
     */
    protected function _hasZeroGrandTotal(Mage_Sales_Model_Quote $quote)
    {
        return (!$quote->validateMinimumAmount() || (!$quote->getGrandTotal() && !$quote->hasNominalItems()));
    }
    /**
     * Check if we can show the paypal button.
     * @return bool
     */
    protected function _canShowPayPalButton()
    {
        return $this->_shouldRenderProductPage()
            && $this->_isPositiveProductPrice()
            && $this->_shouldRenderCartPage()
            && $this->_shouldRenderQuote();
    }

    /**
     * @return self
     */
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        $this->setShortcutHtmlId($this->_htmlId)
            ->setCheckoutUrl($this->getUrl($this->_startAction))
            ->setImageUrl($this->_payPalImageUrl);
        return $this;
    }

    /**
     * Check is "OR" label position before shortcut
     *
     * @return bool
     */
    public function isOrPositionBefore()
    {
        return
            ($this->getIsOnCatalogProductPage() && !$this->getShowOrPosition())
            || ($this->getShowOrPosition()
                && $this->getShowOrPosition() == self::POSITION_BEFORE);
    }

    /**
     * Check is "OR" label position after shortcut
     *
     * @return bool
     */
    public function isOrPositionAfter()
    {
        return
            (!$this->getIsOnCatalogProductPage() && !$this->getShowOrPosition())
            || ($this->getShowOrPosition()
                && $this->getShowOrPosition() == self::POSITION_AFTER);
    }

    /**
     * Get the checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        if (!($this->_checkoutSession instanceof Mage_Checkout_Model_Session)) {
            $this->_checkoutSession = Mage::getSingleton('checkout/session');
        }
        return $this->_checkoutSession;
    }

    /**
     * @return Mage_Sales_Model_Quote|null
     */
    protected function _getQuote()
    {
        if (!$this->getIsQuoteAllowed()) {
            return null;
        }
        if (!($this->_quote instanceof Mage_Sales_Model_Quote)) {
            $this->_quote = $this->_getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    /**
     * @return Mage_Catalog_Model_Product|null
     */
    protected function _getCurrentProduct()
    {
        $prod = Mage::registry('current_product');
        if ($prod instanceof Mage_Catalog_Model_Product) {
            return $prod;
        }
        return null;
    }
    /**
     * Render the block if needed
     *
     * @return string
     */
    protected function _toHtml()
    {
        return $this->_canShowPayPalButton() ? parent::_toHtml() : '';
    }
}
