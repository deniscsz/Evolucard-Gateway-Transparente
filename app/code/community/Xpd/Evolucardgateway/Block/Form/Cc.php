<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Block_Form_Cc extends Mage_Payment_Block_Form_Cc {

    /**
     * Set block template
     */
    protected function _construct() {
        parent::_construct();
        $this->setTemplate('xpd/evolucardgateway/form/cc.phtml');
    }



	public function getSourceModel()
    {
		return Mage::getSingleton('evolucardgateway/source_cartoes');
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        //recupera os tipos disponiveis e preenche o vetor types
        $arrayCartoes = $this->getSourceModel()->toOptionArray();

        $types = array();
        foreach($arrayCartoes as $cartao) {
            $types[$cartao['value']] = $cartao['label'];
        }

        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);

                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }

        return $types;
    }


    /**
     * Retreive payment method form html
     *
     * @return string
     */
    public function getMethodFormBlock() {
        return $this->getLayout()->createBlock('payment/form_cc')
                        ->setMethod($this->getMethod());
    }

    public function getParcelas() {
        $max_parcelas = Mage::getStoreConfig('payment/evolucardgateway/parcelas');
        $valor_minimo = Mage::getStoreConfig('payment/evolucardgateway/valor_minimo');
        $parcelas_sem_juros = Mage::getStoreConfig('payment/evolucardgateway/parcelas_sem_juros');
        $taxa_juros = Mage::getStoreConfig('payment/evolucardgateway/taxa_juros');

        $total = Mage::getSingleton('checkout/cart')->getQuote()->getGrandTotal();

        $totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();

        if (isset($totals["encargo"])) {
            $encargo = $totals["encargo"]->getValue();
        } else {
            $encargo = 0;
        }
        if ($encargo > 0) {
            $total = $total - $encargo;
        }


        $total_com_juros = $total;

        $n = floor($total / $valor_minimo);
        if ($n > $max_parcelas) {
            $n = $max_parcelas;
        } elseif ($n < 1) {
            $n = 1;
        }

        $parcelas = array();
        for ($i = 0; $i < $n; $i++) {
            $total_com_juros *= 1 + ($taxa_juros / 100);

            if ($i + 1 == 1) {
                $label = 'À vista - ' . $this->helper('checkout')->formatPrice($total);
            } elseif ($taxa_juros > 0 && $i + 1 > $parcelas_sem_juros) {
                $label = ($i + 1) . 'x - ' . $this->helper('checkout')->formatPrice($total_com_juros / ($i + 1)) . ' (juros de ' . $taxa_juros . '% ao mês)';
            } else {
                $label = ($i + 1) . 'x - ' . $this->helper('checkout')->formatPrice($total / ($i + 1));
            }
            $parcelas[] = array('parcela' => $i + 1, 'label' => $label);
        }
        return $parcelas;
    }

}
