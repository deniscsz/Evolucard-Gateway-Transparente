<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Model_Source_Cartoes extends Mage_Payment_Model_Source_Cctype
{
    public function toOptionArray()
    {
        $allowed = $this->getAllowedTypes();
        $options = array();

        foreach (Mage::getSingleton('evolucardgateway/config')->getCcTypes() as $code => $name) {
            if (in_array($code, $allowed) || !count($allowed)) {
                $options[] = array(
                   'value' => $code,
                   'label' => $name
                );
            }
        }

        return $options;
    }
}

