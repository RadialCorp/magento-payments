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

class EbayEnterprise_PayPal_Test_Block_Payment_MarkTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    /** @var EbayEnterprise_Eb2cCore_Model_Config_Registry */
    protected $config;
    /** @var EbayEnterprise_PayPal_Helper_Data */
    protected $helper;
    /** @var Mage_Core_Model_Locale */
    protected $locale;
    /** @var EbayEnterprise_PayPal_Block_Payment_Mark */
    protected $block;

    public function setUp()
    {
        parent::setUp();
        $this->locale = $this->getModelMock('core/locale', ['getLocale', 'getLocaleCode']);
        $zendLocale = $this->getMockBuilder('Zend_Locale')
            ->disableOriginalConstructor()
            ->setMethods(['getRegion'])
            ->getMock();
        $this->locale->expects($this->any())
            ->method('getLocale')
            ->will($this->returnValue($zendLocale));
        $zendLocale->expects($this->any())
            ->method('getRegion')
            ->will($this->returnValue('US'));
        $this->locale->expects($this->any())
            ->method('getLocaleCode')
            ->will($this->returnValue('en_US'));
        $this->block = new EbayEnterprise_PayPal_Block_Payment_Mark;
        $this->block->setLocale($this->locale);
    }

    public function provideOverrideImageUrl()
    {
        return [
            [null, 'https://www.paypal.com/en_US/i/logo/PayPal_mark_50x34.gif'],
            ['https://example.com/override/image', 'https://example.com/override/image'],
        ];
    }

    /**
     * Scenario: get the the image url
     *
     * When the getPaymentMarkImageUrl method is called
     * Then the url will be returned using the default image size
     * When a url is set in the config
     * Then that url will be returned
     *
     * @dataProvider provideOverrideImageUrl
     */
    public function testGetPaymentMarkImageUrl($configValue, $expectedUrl)
    {
        $this->config = $this->buildCoreConfigRegistry([
            'markImageSrc' => $configValue,
            'paymentMarkSize' => null,
            'whatIsPageUrl' => null,
        ]);
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $this->block,
            [
                'config' => $this->config,
            ]
        );
        $this->assertSame(
            $expectedUrl,
            $this->block->getPaymentMarkImageUrl()
        );
    }

    public function provideOverrideWhatIsPageUrl()
    {
        return [
            [null, 'https://www.paypal.com/us/cgi-bin/webscr?cmd=xpt/Marketing/popup/OLCWhatIsPayPal-outside'],
            ['https://example.com/override/image', 'https://example.com/override/image'],
        ];
    }

    /**
     * Scenario: Get the "What Is PayPal" localized URL
     *
     * When getWhatIsPayPalUrl is called
     * Then a localized url is returned
     * When a url is set in the config
     * Then that url will be returned
     *
     * @dataProvider provideOverrideWhatIsPageUrl
     */
    public function testGetPaymentMarkUrl($configValue, $expectedUrl)
    {
        $this->config = $this->buildCoreConfigRegistry([
            'markImageSrc' => null,
            'paymentMarkSize' => null,
            'whatIsPageUrl' => $configValue,
        ]);
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $this->block,
            [
                'config' => $this->config,
            ]
        );
        $this->assertSame(
            $expectedUrl,
            $this->block->getWhatIsPayPalUrl()
        );
    }
}
