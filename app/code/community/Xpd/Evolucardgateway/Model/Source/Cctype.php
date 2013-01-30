<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Model_Source_Cctype extends Mage_Payment_Model_Source_Cctype
{
  public function getAllowedTypes()
  {
      return array('VI', 'MC');
  }
}

