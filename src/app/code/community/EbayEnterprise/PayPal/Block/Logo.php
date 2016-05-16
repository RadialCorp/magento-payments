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
 * @method string getIsInCatalogProduct()
 * @method string getConfirmationMessage()
 */
class EbayEnterprise_PayPal_Block_Logo extends Mage_Core_Block_Template
{
    const DEFAULT_LOGO_IMAGE_SRC =
        'https://www.paypalobjects.com/{locale_code}/i/bnr/bnr_{logo_type}.gif';
    const DEFAULT_LOGO_ABOUT_URI =
        'https://www.paypal.com/{locale_code}/cgi-bin/webscr?cmd=xpt/Marketing/popup/OLCWhatIsPayPal-outside';
    /** @var bool Whether the block should be eventually rendered */
    protected $_shouldRender = true;
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry $_config */
    protected $_config;

    public function _construct()
    {
        parent::_construct();
        $this->_config = Mage::helper('ebayenterprise_paypal')->getConfigModel();
        $this->addData(array(
            'locale_code' => Mage::app()->getLocale()->getLocaleCode(),
            // can be assigned in layout
            'logo_type' => $this->getLogoType() ?: $this->_config->logoType
        ));
    }
    /**
     * Normalize a passed in string content that might have one or two
     * known place holders to be replaced.
     * @param string $stringContent
     * @return string
     */
    protected function _normalizeString($stringContent)
    {
        return str_replace(
            array('{locale_code}', '{logo_type}'),
            array($this->getLocaleCode(), $this->getLogoType()),
            $stringContent
        );
    }
    /**
     * Get the URL for the PayPal "about" page
     * @return string
     */
    public function getAboutPaypalPageUrl()
    {
        return $this->_normalizeString(
            $this->_config->logoAboutPageUri ?: self::DEFAULT_LOGO_ABOUT_URI
        );
    }
    /**
     * override to ensure we set the logo type based on what's in
     * the layout update xml
     * @return string
     */
    protected function _toHtml()
    {
        $result = parent::_toHtml();
        $this->setLogoImageUrl(
            $this->_normalizeString($this->_config->logoImageSrc ?: self::DEFAULT_LOGO_IMAGE_SRC)
        );
        return $result;
    }
}
