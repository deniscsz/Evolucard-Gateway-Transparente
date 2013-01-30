<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Model_Config extends Mage_Payment_Model_Config
{
    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getCcTypes()
    {
        $_types = Mage::getConfig()->getNode('global/evolucardgateway/cc/types')->asArray();

        uasort($_types, array('Xpd_Evolucardgateway_Model_Config', 'compareCcTypes'));

        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }
}
