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
class Xpd_Evolucardgateway_Block_Standard_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $session = Mage::getSingleton('checkout/session');
        $html = '<html><body>';
        $html.= $this->__('<center>Redirecionando</center>');
				$html.= '<script type="text/javascript">window.location.href = "' . $session->getRedirectUrl() . '"</script>';
        $html.= '</body></html>';

        return $html;
    }
}

