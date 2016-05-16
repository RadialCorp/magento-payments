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
 * PayPal Standard payment "form"
 */
class EbayEnterprise_PayPal_Block_Payment_Mark extends Mage_Core_Block_Template
{
    const ALT_TEXT_MESSAGE = 'EBAYENTERPRISE_PAYPAL_PAYMENT_MARK_ALT_TEXT';
    const DEFAULT_MARK_IMAGE_SRC =
        'https://www.paypal.com/{locale_code}/i/logo/PayPal_mark_{static_size}.gif';
    const DEFAULT_WHAT_IS_PAYPAL_URL =
        'https://www.paypal.com/%s/cgi-bin/webscr?cmd=xpt/Marketing/popup/OLCWhatIsPayPal-outside';
    const PAYMENT_MARK_37X23 = '37x23';
    const PAYMENT_MARK_50X34 = '50x34';
    const PAYMENT_MARK_60X38 = '60x38';
    const PAYMENT_MARK_180X113 = '180x113';
    /**
     * Config model instance
     *
     * @var EbayEnterprise_Eb2cCore_Model_Config_Registry
     */
    protected $config;
    /** @var EbayEnterprise_PayPal_Helper_Data */
    protected $helper;
    /** @var Mage_Core_Model_Locale */
    protected $locale;
    /** @var string */
    protected $paymentMarkImageUrl;
    /** @var string */
    protected $whatIsPayPalUrl;

    /**
     * Set template and redirect message
     */
    protected function _construct()
    {
        $this->helper = Mage::helper('ebayenterprise_paypal');
        $this->config = $this->helper->getConfigModel();
        $this->setTemplate('ebayenterprise_paypal/payment/mark.phtml');
    }

    /**
     * get the url to an "about paypal" page
     *
     * @return string
     */
    public function getWhatIsPayPalUrl()
    {
        if (!$this->whatIsPayPalUrl) {
            $this->whatIsPayPalUrl = $this->generateWhatIsPaypalUrl();
        }
        return $this->whatIsPayPalUrl;
    }

    /**
     * get the url for the image displayed for the payment
     *
     * @return string
     */
    public function getPaymentMarkImageUrl()
    {
        if (!$this->paymentMarkImageUrl) {
            $this->paymentMarkImageUrl = $this->generatePaymentMarkImageUrl();
        }
        return $this->paymentMarkImageUrl;
    }

    /**
     * Get PayPal "mark" image URL
     * Supposed to be used on payment methods selection
     * $staticSize is applicable for static images only
     *
     * @param string $staticSize
     */
    protected function generatePaymentMarkImageUrl()
    {
        // get the static size set on the block or
        $staticSize = $this->getStaticSize() ?: $this->config->paymentMarkSize;
        switch ($staticSize) {
            case self::PAYMENT_MARK_37X23:
            case self::PAYMENT_MARK_50X34:
            case self::PAYMENT_MARK_60X38:
            case self::PAYMENT_MARK_180X113:
                break;
            default:
                $staticSize = self::PAYMENT_MARK_50X34;
        }
        $markImageSrc = $this->config->markImageSrc ?: self::DEFAULT_MARK_IMAGE_SRC;
        return str_replace(
            array('{locale_code}', '{static_size}'),
            array($this->getLocale()->getLocaleCode(), $staticSize),
            $markImageSrc
        );
    }

    /**
     * Get "What Is PayPal" localized URL
     *
     * @return string
     */
    protected function generateWhatIsPayPalUrl()
    {
        return $this->config->whatIsPageUrl
            ?: sprintf(self::DEFAULT_WHAT_IS_PAYPAL_URL, strtolower($this->getCountryCode()));
    }

    /**
     * get the country code for use in generating the what is paypal url
     *
     * @return string
     */
    protected function getCountryCode()
    {
        $locale = $this->getLocale();
        // get the region code from the locale's underlying
        // Zend_Locale object
        $countryCode = $locale->getLocale()->getRegion();
        return $countryCode;
    }

    /**
     * get the locale
     *
     * @return Mage_Core_Model_Locale
     */
    public function getLocale()
    {
        if (!$this->locale) {
            $this->locale = $this->getGlobalLocale();
        }
        return $this->locale;
    }

    /**
     * set the locale
     *
     * @param Mage_Core_Model_Locale
     */
    public function setLocale(Mage_Core_Model_Locale $locale)
    {
        $this->locale = $locale;
    }

    /**
     * get the current global locale
     *
     * @return Mage_Core_Model_Locale
     */
    protected function getGlobalLocale()
    {
        return Mage::app()->getLocale();
    }

    /**
     * get the translated alt text for the image
     *
     * @return string
     */
    public function getPaymentMarkAltText()
    {
        return $this->helper->__(self::ALT_TEXT_MESSAGE);
    }
}
