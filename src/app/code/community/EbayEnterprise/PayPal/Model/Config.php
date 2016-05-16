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

class EbayEnterprise_PayPal_Model_Config extends EbayEnterprise_Eb2cCore_Model_Config_Abstract
{
    protected $_configPaths
        = array(
            'is_enabled'                         => 'ebayenterprise_paypal/general/active',
            'title'                              => 'ebayenterprise_paypal/general/title',
            'sort_order'                         => 'ebayenterprise_paypal/general/sort_order',
            'is_sandboxed'                       => 'ebayenterprise_paypal/general/sandbox_flag',
            'visible_on_cart'                    => 'ebayenterprise_paypal/general/visible_on_cart',
            'visible_on_product'                 => 'ebayenterprise_paypal/general/visible_on_product',
            'order_status'                       => 'ebayenterprise_paypal/general/order_status',
            'allowspecific'                      => 'ebayenterprise_paypal/general/allowspecific',
            'specificcountry'                    => 'ebayenterprise_paypal/general/specificcountry',
            'transfer_lines'                     => 'ebayenterprise_paypal/general/transfer_lines',
            // config that should not be put on the admin
            'payment_action'                     => 'ebayenterprise_paypal/general/payment_action',
            'min_order_total'                    => 'ebayenterprise_paypal/general/min_order_total',
            'max_order_total'                    => 'ebayenterprise_paypal/general/max_order_total',
            'payment_mark_size'                  => 'ebayenterprise_paypal/general/payment_mark_size',
            'logo_type'                          => 'ebayenterprise_paypal/general/logo_type',
            // api configuration
            'api_operation_set_express_checkout' => 'ebayenterprise_paypal/api/operation_set_express_checkout',
            'api_operation_get_express_checkout' => 'ebayenterprise_paypal/api/operation_get_express_checkout',
            'api_operation_do_express_checkout'  => 'ebayenterprise_paypal/api/operation_do_express_checkout',
            'api_operation_do_authorization'     => 'ebayenterprise_paypal/api/operation_do_authorization',
            'api_operation_do_void'              => 'ebayenterprise_paypal/api/operation_do_void',
            'api_service'                        => 'ebayenterprise_paypal/api/service',
            // URL and Image configuration
            'logo_image_src'                     => 'ebayenterprise_paypal/url/logo_image_src',
            'logo_about_page_uri'                => 'ebayenterprise_paypal/url/logo_about_page_uri',
            'mark_image_src'                     => 'ebayenterprise_paypal/url/mark_image_src',
            'what_is_page_url'                   => 'ebayenterprise_paypal/url/what_is_page_url',
            'shortcut_express_checkout_button'   => 'ebayenterprise_paypal/url/shortcut_express_checkout_button',
            'redirect_uri'                       => 'ebayenterprise_paypal/url/redirect_uri',
        );

    public function __construct()
    {
        // add config entries for compatibility with the hardwired calls to
        // Mage_Payment_Model_method_Abstract::getConfigData
        $this->_configPaths['active'] = $this->_configPaths['is_enabled'];
        $this->_configPaths['sandbox_flag']
            = $this->_configPaths['is_sandboxed'];
    }
}
