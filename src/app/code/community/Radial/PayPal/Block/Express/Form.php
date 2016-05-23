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
class Radial_PayPal_Block_Express_Form extends Mage_Payment_Block_Form
{
    const WILL_REDIRECT_MESSAGE = 'RADIAL_PAYPAL_WILL_REDIRECT_MESSAGE';
    const PAYMENT_MARK = 'radial_paypal/payment_mark';

    protected $_template = 'radial_paypal/payment/redirect.phtml';

    /**
     * Set template and redirect message
     */
    protected function _construct()
    {
        $markClass = Mage::getConfig()->getBlockClassName(static::PAYMENT_MARK);
        $mark = new $markClass();
        $markHtml = $mark->toHtml();
        $helper = Mage::helper('radial_paypal');
        $translatedRedirectMessage = $helper->__(static::WILL_REDIRECT_MESSAGE);
        $this->setMethodTitle(''); // Title conflicts with PayPal mark
        $this->setMethodLabelAfterHtml($markHtml);
        $this->setRedirectMessage($translatedRedirectMessage);
        parent::_construct();
    }
}
