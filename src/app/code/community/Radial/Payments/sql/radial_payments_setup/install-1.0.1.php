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
 *
 * @var Mage_Sales_Model_Resource_Setup $installer
 */
$installer = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$connection->addColumn(
    $this->getTable('sales/invoice'),
    'delivery_status',
    "INTEGER DEFAULT NULL"
);

$connection->addColumn(
    $this->getTable('sales/creditmemo'),
    'delivery_status',
    "INTEGER DEFAULT NULL"
);

$installer->endSetup();
