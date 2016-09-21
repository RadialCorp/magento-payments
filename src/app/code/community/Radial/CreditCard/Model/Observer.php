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

class Radial_CreditCard_Model_Observer
{
    /**
     * handle payment settlement status event
     * request.
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function handlePaymentSettlementsStatusEvent(Varien_Event_Observer $observer)
    {
        Mage::getModel(
            'radial_creditcard/events_settlementStatus', 
            ['payload' => $observer->getEvent()->getPayload()]
        )->process();
    }

    public function issueCorsHeader(Varien_Event_Observer $observer)
    {
	$urls = array( Mage::getBaseUrl(), Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN), Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN, array('_secure'=>true)),
		Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA), Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA,  array('_secure'=>true)),
		Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS), Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS, array('_secure'=>true)));

	$hostnames = array();

	foreach( $urls as $url )
	{
		$hostnames[] = parse_url($url, PHP_URL_HOST);
	}

	$hostnames_uniq = array_unique($hostnames);

	if ($this->getRequest()->getServer('http_origin') != ''){
	  foreach ($hostnames_uniq as $allowedOrigin) {
	    if (preg_match('#' . $allowedOrigin . '#', $this->getRequest()->getServer('http_origin'))) {
	      $this->getResponse()->setHeader('Access-Control-Allow-Origin: ' . $this->getRequest()->getServer('http_origin'));
	      $this->getResponse()->setHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
	      $this->getResponse()->setHeader('Access-Control-Max-Age: 1000');
	      $this->getResponse()->setHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
	      break;
	    }
	 }
    }
}
