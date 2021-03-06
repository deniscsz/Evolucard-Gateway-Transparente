<?php
/**
 * Octagono Ecommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.octagonoecommerce.com.br/eula-licenca-usuario-final.html
 *
 *
 * @category   Cielo
 * @package    Octagono_Cielo
 * @copyright  Copyright (c) 2009-2011 - Octagono Ecommerce - www.octagonoecommerce.com.br
 * @license    http://www.octagonoecommerce.com.br/eula-licenca-usuario-final.html
 */
class Xpd_Evolucardgateway_Block_Info extends Mage_Payment_Block_Info_Ccsave
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('xpd/evolucardgateway/info.phtml');
    }

	/**
     * Retrieve current order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        $order = Mage::registry('current_order');

		$info = $this->getInfo();

		if (!$order) {
			if ($this->getInfo() instanceof Mage_Sales_Model_Order_Payment) {
				$order = $this->getInfo()->getOrder();
			}
		}

		return($order);
    }
    
    public function returnTransaction() {
        $order = $this->getOrder();
        if(isset($order)) {
            return $order->getPayment()->getEvolucardTransactionId();//$order->getTransactionEvc();
        }
        else {
            return NULL;
        }
    }
    
    public function returnAcqNumberTransaction() {
        $order = $this->getOrder();
        if(isset($order)) {
            return $order->getPayment()->getAcqNumberTransaction();//$order->getTransactionEvc();
        }
        else {
            return NULL;
        }
    }
    
    public function returnAuthorizationNumber() {
        $order = $this->getOrder();
        if(isset($order)) {
            return $order->getPayment()->getAuthorizationNumber();//$order->getTransactionEvc();
        }
        else {
            return NULL;
        }
    }
}

