<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Block_Info_Cc extends Mage_Payment_Block_Info_Ccsave
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
    
}

