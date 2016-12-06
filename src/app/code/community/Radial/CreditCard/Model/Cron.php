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

class Radial_CreditCard_Model_Cron
{
    /**
     * Send Auth Cancel for Quotes Where the Created At Date > Threshold AND
     * An Order Does Not Exist For the Quotes Reserved Order ID AND
     * The Payment Method is Radial_CreditCard AND The Last Response Code Was AVS, DECL, DECLF
     *
     * @param Varien_Event_Observer
     */
    public function authCancelGrace()
    {
	$authGrace = Mage::getStoreConfig('payment/radial_creditcard/authgrace');
	$adapter = Mage::getSingleton('core/resource')->getConnection('sales_read');
	$minutes = $authGrace * 60;
	$from = $adapter->getDateSubSql(
	    $adapter->quote(now()), 
	    $minutes, 
	    Varien_Db_Adapter_Interface::INTERVAL_MINUTE
	);

        $quoteCollection = Mage::getResourceModel('sales/quote_collection')
                                ->addFieldToFilter('created_at', array('to' => $from))
                                ->addFieldToFilter('is_active', array('eq' => 1 ))
                                ->setPageSize(100);

        $pages = $quoteCollection->getLastPageNumber();
        $currentPage = 1;

        do
        {
                $quoteCollection->setCurPage($currentPage);
                $quoteCollection->load();

                foreach ($quoteCollection as $quote) {
                        $payment = $quote->getPayment();

                        if( $payment->getMethod() == 'radial_creditcard' )
                        {
                                $validForCancel = array( 'AVS', 'DECL', 'DECLF' );

                                if( in_array( $payment->getAdditionalInformation()['risk_response_code'], $validForCancel))
                                {
                                        $payment->getMethodInstance()->void($payment);
                                        $prev = $payment->getAdditionalInformation()['risk_response_code'];
                                        $payment->getAdditionalInformation()['risk_response_code'] = $prev . '-C';
                                        $payment->save();
                                }
                        }

                }

                $currentPage++;
                $quoteCollection->clear();
        } while ($currentPage <= $pages);
    }
}
