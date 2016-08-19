<?php
/**
 * Copyright (c) 2013-2016 Radial, Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @copyright   Copyright (c) 2013-2016 Radial, Inc. (http://www.radial.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @var Mage_Sales_Model_Resource_Setup $this
 */
$this->startSetup();

/** @var Mage_Sales_Model_Order_Status $status */
$status = Mage::getModel('sales/order_status');

// Add a new status
$status->setStatus(Radial_Payments_Model_Invoicing_Order::STATUS_READY_CODE)
    ->setLabel('Ready to Ship')
    ->assignState(Mage_Sales_Model_Order::STATE_PROCESSING)
    ->save();

$this->endSetup();
