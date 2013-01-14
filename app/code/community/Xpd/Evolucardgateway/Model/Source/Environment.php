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

