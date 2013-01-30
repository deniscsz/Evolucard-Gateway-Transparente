<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Model_Source_Environment
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 0,
                'label' => 'Teste'
            ),
            array(
                'value' => 1,
                'label' => 'Produção'
            ),
        );
    }
}

