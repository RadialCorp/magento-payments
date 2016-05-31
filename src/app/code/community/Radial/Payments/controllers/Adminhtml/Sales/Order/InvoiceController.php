<?php

require_once Mage::getModuleDir('controllers','Mage_Adminhtml').DS.'Sales'.DS.'Order'.DS.'InvoiceController.php';

class Radial_Payments_Adminhtml_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController
{

    /**
     * Retry settlement action
     */
    public function settlementAction()
    {
        if ($invoice = $this->_initInvoice()) {
            try {
                $order = $invoice->getOrder();
                $payment = $order->getPayment();
                $payment->getMethodInstance()->processInvoice($invoice, $payment);
                $this->_saveInvoice($invoice);
                $this->_getSession()->addSuccess($this->__('The settlement request has been resent.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Process invoice error.'));
            }
            $this->_redirect('*/*/view', ['invoice_id'=>$invoice->getId()]);
        } else {
            $this->_forward('noRoute');
        }
    }
}
