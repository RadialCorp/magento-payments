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

class Radial_PayPal_Block_Express_CartShortcut extends Radial_PayPal_Block_Express_Shortcut
{
    /**
     * Start express action
     *
     * @var string
     */
    protected $_startAction =
        'radial_paypal_express/checkout/start/button/' .
        Radial_PayPal_Model_Express_Checkout::BUTTON_CART;
}
