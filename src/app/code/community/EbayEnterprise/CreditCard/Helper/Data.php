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

class EbayEnterprise_CreditCard_Helper_Data extends Mage_Core_Helper_Abstract implements EbayEnterprise_Eb2cCore_Helper_Interface
{
    const UNKNOWN_CARD_TYPE = 'EbayEnterprise_CreditCard_Unknown_Card_Type';
    /** @var Mage_Payment_Model_Config */
    protected $_globalPaymentConfig;

    public function __construct()
    {
        $this->_globalPaymentConfig = Mage::getModel('payment/config');
    }
    public function getConfigModel($store = null)
    {
        return Mage::getModel('eb2ccore/config_registry')
            ->setStore($store)
            ->addConfigModel(Mage::getSingleton('ebayenterprise_creditcard/config'));
    }
    /**
     * Get a payment/config model, used to get global Magento payment
     * configurations.
     * @return Mage_Payment_Model_Config
     */
    protected function _getGlobalPaymentConfig()
    {
        return $this->_globalPaymentConfig;
    }
    /**
     * Get the ROM Tender Type for the Magento CC Type.
     * @param  string $creditCardType
     * @return string
     */
    public function getTenderTypeForCcType($creditCardType)
    {
        $types = $this->getConfigModel()->tenderTypes;
        if (isset($types[$creditCardType])) {
            return $types[$creditCardType];
        }
        throw Mage::exception('EbayEnterprise_CreditCard', self::UNKNOWN_CARD_TYPE);
    }
    /**
     * Scrub the auth request XML message of any sensitive data - CVV, CC number.
     * @param  string $xml
     * @return string
     */
    public function cleanAuthXml($xml)
    {
        $xml = preg_replace('#(\<(?:Encrypted)?CardSecurityCode\>).*(\</(?:Encrypted)?CardSecurityCode\>)#', '$1***$2', $xml);
        $xml = preg_replace('#(\<(?:Encrypted)?PaymentAccountUniqueId.*?\>).*(\</(?:Encrypted)?PaymentAccountUniqueId\>)#', '$1***$2', $xml);
        return $xml;
    }
    /**
     * Get all credit card types that are known by Magento and mapped to ROM
     * tender types.
     * @return array Key value pair of type code => type name
     */
    public function getAvailableCardTypes()
    {
        // both arrays keyed by Magento credit card type code
        return array_intersect_key(
            $this->_getGlobalPaymentConfig()->getCcTypes(),
            $this->getConfigModel()->tenderTypes
        );
    }
}
