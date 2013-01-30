<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
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

