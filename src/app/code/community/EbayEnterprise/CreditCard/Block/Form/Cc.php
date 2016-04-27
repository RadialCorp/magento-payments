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

class EbayEnterprise_CreditCard_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $_config;

    protected function _construct()
    {
        parent::_construct();
        $this->_config = Mage::helper('ebayenterprise_creditcard')->getConfigModel();
        $this->setTemplate('ebayenterprise_creditcard/form/cc.phtml');
    }
    /**
     * Get the client side encryption key. Encryption key expected to be free
     * of whitespace in the template so strip any out if the key was formatted
     * with, or otherwise contains, any whitespace.
     * @return string
     */
    public function getEncryptionKey()
    {
        return $this->_config->encryptionKey;
    }
    /**
     * Get the config flag for if client side encryption is enabled.
     * @return bool
     */
    public function isUsingClientSideEncryption()
    {
        return $this->_config->useClientSideEncryptionFlag;
    }
    /**
     * Get the names of form fields to use in the CC form.
     * @return array
     */
    public function getFormFields()
    {
        $code = $this->getMethodCode();
        return array(
            'method' => "{$code}_payment_method",
            'number' => "{$code}_cc_number",
            'type' => "{$code}_cc_type",
            'expiration_month' => "{$code}_expiration",
            'expiration_year' => "{$code}_expiration_yr",
            'cid' => "{$code}_cc_cid",
            'issue' => "{$code}_cc_issue",
            'start_month' => "{$code}_start_month",
            'start_year' => "{$code}_start_year",
            'last_four' => "{$code}_cc_last4",
        );
    }
}
