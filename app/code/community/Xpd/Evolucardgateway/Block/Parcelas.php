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
class Octagono_Cielo_Block_Parcelas extends Mage_Core_Block_Template
{

    protected $_showScripts = true;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('xpd/evolucardgateway/parcelas.phtml');
    }

    protected function _beforeToHtml()
    {
        $this->_prepareForm();
        return parent::_beforeToHtml();
    }


    public function getCielo()
    {
        return Mage::getSingleton('cielo/cielo');
    }




    public function getProductFinalPrice()
    {
        $price = preg_replace("/^R\\$[ ]*/i", "", $this->getRequest()->getParam('price'));
        $price = str_replace(".", "", $price);
        $price = str_replace(",", ".", $price);

        if ($price > 0) {
            $this->_showScripts = false;
        } else {

            $productId = $this->getRequest()->getParam('id');
            if (!Mage::registry('product') && $productId) {
                $product = Mage::getModel('catalog/product')->load($productId);
                Mage::register('product', $product);
            } else {
                $product = Mage::registry('product');
            }

            if ($product) {
                $price = $product->getFinalPrice();
            } else {
                $price = 0;
            }

        }

        return $price;

    }


        public function getParcelas(){
		        $max_parcelas = Mage::getStoreConfig('payment/cielo/parcelas');
		        $valor_minimo = Mage::getStoreConfig('payment/cielo/valor_minimo');
		        $parcelas_sem_juros = Mage::getStoreConfig('payment/cielo/parcelas_sem_juros');
		        $taxa_juros = Mage::getStoreConfig('payment/cielo/taxa_juros');

		        $total = $this->getProductFinalPrice();

		        $total_com_juros = $total;

		        $n = floor($total / $valor_minimo);
		        if($n > $max_parcelas){
			        $n = $max_parcelas;
		        }elseif($n < 1){
			        $n = 1;
		        }
		        
		        Mage::getSingleton('core/session')->setNumparevo($n);

		        $parcelas = array();
	            for ($i=0; $i < $n; $i++){
			        $total_com_juros *= 1 + ($taxa_juros / 100);

			        if($i+1 == 1){
				        $label = '1x sem juros de '.Mage::helper('checkout')->formatPrice($total);
			        }elseif($taxa_juros > 0 && $i+1 > $parcelas_sem_juros){
				        $label = ($i+1).'x com juros de '.Mage::helper('checkout')->formatPrice($total_com_juros/($i + 1));
			        }else{
				        $label = ($i+1).'x sem juros de '.Mage::helper('checkout')->formatPrice($total/($i + 1));
			        }
			        $parcelas[] = array('parcela' => $i+1, 'label' => $label);
		        }
		        return $parcelas;
	        }


    protected function _prepareForm()
    {
        $cielo = $this->getCielo();
        $helper = Mage::helper("cielo");


    }

}

