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

use \eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderContext;

class EbayEnterprise_PayPal_Test_Model_Order_Create_ContextTest extends EbayEnterprise_Eb2cCore_Test_Base
{
    public function testUpdateOrderContext()
    {
        $payment = Mage::getModel('sales/order_payment', ['method' => 'ebayenterprise_paypal_express']);
        $order = $this->getModelMock('sales/order', ['getPayment']);
        $order->expects($this->any())
            ->method('getPayment')
            ->will($this->returnValue($payment));
        $payload = $this->getMock('\eBayEnterprise\RetailOrderManagement\Payload\Order\IOrderContext');
        $methods = ['setPayPalPayerId', 'setPayPalPayerStatus', 'setPayPalAddressStatus'];
        foreach ($methods as $method) {
            $payload->expects($this->once())
                ->method($method)
                ->will($this->returnSelf());
        }
        $handler = Mage::getModel('ebayenterprise_paypal/order_create_context');
        $handler->updateOrderContext($order, $payload);
    }
}
