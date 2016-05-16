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

class Radial_Transactions_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @param Mage_Sales_Model_Order_Payment
     * @param bool
     */
    public function preparePaymentForTransaction(Mage_Sales_Model_Order_Payment $payment, $isClosed = false)
    {
        $transactionId = $this->getTransactionNumber($payment);
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed($isClosed);
        $payment->setTransactionAdditionalInfo(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $this->getPaymentAdditionalInfo($payment)
        );
    }

    protected function getTransactionNumber(Mage_Sales_Model_Order_Payment $payment)
    {
        $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->setOrderFilter($payment->getOrder())
            ->addPaymentIdFilter($payment->getId());
        return $collection->getSize() + 1;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment
     * @return array
     */
    protected function getPaymentAdditionalInfo(Mage_Sales_Model_Order_Payment $payment)
    {
        return [
            'Authorized'    => $payment->getAmountAuthorized(),
            'Paid'          => $payment->getAmountPaid()
        ];
    }
}
