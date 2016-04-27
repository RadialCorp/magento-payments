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

class EbayEnterprise_CreditCard_Test_Helper_DataTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /**
     * Test getting the ROM tender type for a given Magento CC type via the config registry.
     * @param string $ccType Credit card type code
     * @dataProvider dataProvider
     */
    public function testGetTenderTypeForCcType($ccType)
    {
        $tenderType = 'VC';
        $mappedType = 'VI';
        $helper = $this->getHelperMock('ebayenterprise_creditcard/data', array('getConfigModel'));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($this->buildCoreConfigRegistry(
                array('tenderTypes' => array($mappedType => $tenderType))
            )));
        if ($ccType !== $mappedType) {
            $this->setExpectedException('EbayEnterprise_CreditCard_Exception');
        }
        $this->assertSame($tenderType, $helper->getTenderTypeForCcType($ccType));
    }
    /**
     * Test cleaning the CC Auth XML to remove sensitive data - e.g. CardSecurityCode
     * and PaymentAccountUniqueId values.
     */
    public function testCleanAuthXml()
    {
        $xml = '<_><CardSecurityCode>123</CardSecurityCode><PaymentAccountUniqueId isToken="true"><[!CDATA[1111411AP+111111333]]></PaymentAccountUniqueId></_>';
        $this->assertSame(
            '<_><CardSecurityCode>***</CardSecurityCode><PaymentAccountUniqueId isToken="true">***</PaymentAccountUniqueId></_>',
            Mage::helper('ebayenterprise_creditcard')->cleanAuthXml($xml)
        );
    }
    /**
     * Same as self::testCleanAuthXml, just with encrypted nodes
     */
    public function testCleanAuthXmlEncrypted()
    {
        $xml = '<_><EncryptedCardSecurityCode>$bt4|javascript_1_3_10$ThCaUU65veFuC2A7AK4CIuM=</EncryptedCardSecurityCode><EncryptedPaymentAccountUniqueId>$bt4|javascript_1_3_10$gzIUBGAUh6MRehbaWEcw+0047SxTxc=</EncryptedPaymentAccountUniqueId></_>';
        $this->assertSame(
            '<_><EncryptedCardSecurityCode>***</EncryptedCardSecurityCode><EncryptedPaymentAccountUniqueId>***</EncryptedPaymentAccountUniqueId></_>',
            Mage::helper('ebayenterprise_creditcard')->cleanAuthXml($xml)
        );
    }
    /**
     * Test getting credit card types that are available for use with eBay Enterprise
     * credit cards. Cards must be configured globally in Magento and mapped to
     * ROM tender types.
     */
    public function testGetAvailableCardTypes()
    {
        $magePaymentConfig = $this->getModelMock('payment/config', array('getCcTypes'));
        // getCcTypes returns key/value pairs of CC type code => CC type name for all
        // credit card types Magento knows about - configured at global/payment/cc/types
        $magePaymentConfig->expects($this->any())
            ->method('getCcTypes')
            ->will($this->returnValue(array('AE' => 'American Express', 'VI' => 'Visa', 'SO' => 'Solo')));

        // mapping of credit card types in Magento to ROM tender types
        $mappedTenderTypes = array('AE' => 'AM', 'VI' => 'VC');
        $config = $this->buildCoreConfigRegistry(array('tenderTypes' => $mappedTenderTypes));

        $helper = $this->getHelperMock('ebayenterprise_creditcard', array('getConfigModel', '_getGlobalPaymentConfig'));
        $helper->expects($this->any())
            ->method('getConfigModel')
            ->will($this->returnValue($config));
        $helper->expects($this->any())
            ->method('_getGlobalPaymentConfig')
            ->will($this->returnValue($magePaymentConfig));
        // Availabl types consists of types Magento knows about and are mapped to
        // ROM tender types
        $availableTypes = array('AE' => 'American Express', 'VI' => 'Visa');
        $this->assertSame($availableTypes, $helper->getAvailableCardTypes());
    }
}
