<?php
 
class Radial_Payments_Adminhtml_AdminhtmlController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
	//Load Layout
	$this->loadLayout();

        //Render Layout
        $this->renderLayout();
    }

    /**
     * Reset Messages at Maximum Retries
     */ 
    public function messageResetAction()
    {
        Mage::getSingleton('adminhtml/session')->addSuccess("Successfully Reset Payments Messages at Maximum Transmission");
	$maxretries = Mage::getStoreConfig('radial_core/payments/maxretries');

	$pendingCreditMemo = Mage::getModel('sales/order_creditmemo')->getCollection()->setPageSize(100)
                        ->addFieldToFilter('state', Mage_Sales_Model_Order_Creditmemo::STATE_OPEN)
			->addFieldToFilter('delivery_status', $maxretries);

        $pages = $pendingCreditMemo->getLastPageNumber();
        $currentPage = 1;

        do
        {
                $pendingCreditMemo->setCurPage($currentPage);
                $pendingCreditMemo->load();

                foreach($pendingCreditMemo as $object)
                {
                        $object->setDeliveryStatus(0);
                        $object->save();
                }

                $currentPage++;
                $pendingCreditMemo->clear();
        } while ($currentPage <= $pages);

	$pendingInvoices = Mage::getModel('sales/order_invoice')->getCollection()->setPageSize(100)
                                        ->addFieldToFilter('state', Mage_Sales_Model_Order_Invoice::STATE_OPEN)
					->addFieldToFilter('delivery_status', $maxretries);

	$pages = $pendingInvoices->getLastPageNumber();
        $currentPage = 1;

        do
        {
                $pendingInvoices->setCurPage($currentPage);
                $pendingInvoices->load();

                foreach($pendingInvoices as $object)
                {
                        $object->setDeliveryStatus(0);
                        $object->save();
                }

                $currentPage++;
                $pendingInvoices->clear();
        } while ($currentPage <= $pages);

        $this->_redirect('adminhtml/system_config/edit/section/radial_core');
    }

    /**
     * Purge all messages in the retry queue
     */ 
    public function purgeRetryQueueAction()
    {
        Mage::getSingleton('adminhtml/session')->addSuccess("Successfully Purged Retry Payments Messages Queue");
	$maxretries = Mage::getStoreConfig('radial_core/payments/maxretries');

	 $pendingCreditMemo = Mage::getModel('sales/order_creditmemo')->getCollection()->setPageSize(100)
                        ->addFieldToFilter('state', Mage_Sales_Model_Order_Creditmemo::STATE_OPEN);

        $pages = $pendingCreditMemo->getLastPageNumber();
        $currentPage = 1;

        do
        {
                $pendingCreditMemo->setCurPage($currentPage);
                $pendingCreditMemo->load();

                foreach($pendingCreditMemo as $object)
                {
                        $object->setDeliveryStatus(0);
                        $object->save();
                }

                $currentPage++;
                $pendingCreditMemo->clear();
        } while ($currentPage <= $pages);

        $pendingInvoices = Mage::getModel('sales/order_invoice')->getCollection()->setPageSize(100)
                                        ->addFieldToFilter('state', Mage_Sales_Model_Order_Invoice::STATE_OPEN);

        $pages = $pendingInvoices->getLastPageNumber();
        $currentPage = 1;

        do
        {
                $pendingInvoices->setCurPage($currentPage);
                $pendingInvoices->load();

                foreach($pendingInvoices as $object)
                {
                        $object->setDeliveryStatus(0);
                        $object->save();
                }

                $currentPage++;
                $pendingInvoices->clear();
        } while ($currentPage <= $pages);

        $this->_redirect('adminhtml/system_config/edit/section/radial_core');
    }
}
