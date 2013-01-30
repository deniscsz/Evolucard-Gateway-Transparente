<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Model_Source_Parcelamento
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 2,
                'label' => 'Loja'
            ),
            array(
                'value' => 3,
                'label' => 'Administradora'
            ),
        );
    }
}

