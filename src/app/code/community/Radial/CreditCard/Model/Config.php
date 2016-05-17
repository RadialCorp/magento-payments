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

class Radial_CreditCard_Model_Config extends Radial_Core_Model_Config_Abstract
{
    protected $_configPaths = array(
        'api_authorize' => 'radial_creditcard/api/operation_authorize',
        'api_confirm_funds' => 'radial_creditcard/api/operation_confirm',
        'api_settlement' => 'radial_creditcard/api/operation_capture',
        'api_auth_cancel' => 'radial_creditcard/api/operation_cancel',
        'api_service' => 'radial_creditcard/api/service',
        'encryption_key' => 'payment/radial_creditcard/encryption_key',
        'tender_types' => 'radial_creditcard/tender_types',
        'use_client_side_encryption' => 'payment/radial_creditcard/use_client_side_encryption',
    );
}
