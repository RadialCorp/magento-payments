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

use eBayEnterprise\RetailOrderManagement\Payload\OrderEvents\IPaymentAuthCancelReply;

class Radial_Payments_Model_Events_AuthCancel
{
    /** @var  IAuthCancel */
    protected $payload;
    /** @var Mage_Core_Helper_Data */
    protected $helper;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $context;
    /**
     * @param array $initParams Must include the payload key:
     *                          - 'payload' => IAuthCancel
     *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $initParams = [])
    {
        list($this->payload, $this->helper, $this->logger, $this->context) = $this->checkTypes(
            $initParams['payload'],
            $this->nullCoalesce($initParams, 'helper', Mage::helper('core')),
            $this->nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     * @param  IPaymentAuthCancelReply
     * @param  Mage_Core_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Data
     * @param  EbayEnterprise_MageLog_Helper_Context
     * @return array
     */
    protected function checkTypes(
        IPaymentAuthCancelReply $payload,
        Mage_Core_Helper_Data $helper,
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context
    ) {
        return func_get_args();
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     * @param  array $arr
     * @param  string|int $field Valid array key
     * @param  mixed $default
     * @return mixed
     */
    protected function nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @return self
     */
    public function process()
    {
        $order = $this->getOrderModel()->loadByIncrementId($this->payload->getOrderId());

	if( $order->getId() )
	{
        	$statusHistoryComment = $this->helper->__(
        	    'AuthCancel %s for %s was %s',
        	    $this->payload->getTenderType(),
        	    $this->helper->currency($this->payload->getAmount(), true, false),
        	    $this->payload->getResponseCode()
        	);
        	$order->addStatusHistoryComment($statusHistoryComment);
        	$order->save();
	}

        return $this;
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getOrderModel()
    {
        return Mage::getModel('sales/order');
    }
}
