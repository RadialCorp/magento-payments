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

class Radial_CreditCard_Model_Events_SettlementStatus
{
    /** @var  todo type hint $_payload */
    protected $_payload;
    /** @var EbayEnterprise_MageLog_Helper_Data */
    protected $_logger;
    /** @var EbayEnterprise_MageLog_Helper_Context */
    protected $_context;
    /**
     * @param array $initParams Must include the payload key:
     *                          - 'payload' => OrderEvents\OrderCreditIssued
     *                          - 'logger' => EbayEnterprise_MageLog_Helper_Data
     *                          - 'context' => EbayEnterprise_MageLog_Helper_Context
     */
    public function __construct(array $initParams = [])
    {
        list($this->_payload, $this->_logger, $this->_context) = $this->_checkTypes(
            $initParams['payload'],
            $this->_nullCoalesce($initParams, 'logger', Mage::helper('ebayenterprise_magelog')),
            $this->_nullCoalesce($initParams, 'context', Mage::helper('ebayenterprise_magelog/context'))
        );
    }

    /**
     * Type hinting for self::__construct $initParams
     * @param  OrderEvents\IOrderCreditIssued $payload
     * @param  EbayEnterprise_MageLog_Helper_Data $logger
     * @param  EbayEnterprise_MageLog_Helper_Context $context
     * @return array
     */
    protected function _checkTypes(
        /*OrderEvents\IOrderCreditIssued */$payload, // todo type check
        EbayEnterprise_MageLog_Helper_Data $logger,
        EbayEnterprise_MageLog_Helper_Context $context
    ) {
        return array($payload, $logger, $context);
    }

    /**
     * Return the value at field in array if it exists. Otherwise, use the default value.
     * @param  array $arr
     * @param  string|int $field Valid array key
     * @param  mixed $default
     * @return mixed
     */
    protected function _nullCoalesce(array $arr, $field, $default)
    {
        return isset($arr[$field]) ? $arr[$field] : $default;
    }

    /**
     * @return self
     */
    public function process()
    {
        $logMessage = 'settlement status response: {status}';
        $this->_logger->warning($logMessage, $this->_context->getMetaData(__CLASS__, ['status' => $this->_payload->getSettlementStatus()]));

        // todo process response

        return $this;
    }
}
