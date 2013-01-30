<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Model_Source_Modo
{
    public function toOptionArray()
    {
        return array(
            array('value' => '0', 'label' => 'Homologação'),
            array('value' => '1', 'label' => 'Produção')
        );
    }
}