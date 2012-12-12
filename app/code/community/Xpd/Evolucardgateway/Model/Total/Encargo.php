<?php

/**
 * Your custom total model
 *
 */
class Xpd_Evolucardgateway_Model_Total_Encargo extends Mage_Sales_Model_Quote_Address_Total_Tax {

    /**
     * Constructor that should initiaze 
     */
    public function __construct() {
        $this->setCode('encargo');
    }

    /**
     * Used each time when collectTotals is invoked
     * 
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Your_Module_Model_Total_Custom
     */
    public function collect(Mage_Sales_Model_Quote_Address $address){

        $paymentMethod = Mage::app()->getFrontController()->getRequest()->getParam('payment');
        $paymentMethod = Mage::app()->getStore()->isAdmin() && isset($paymentMethod['method']) ? $paymentMethod['method'] : null;
        if ($paymentMethod != 'evolucardgateway' && (!count($address->getQuote()->getPaymentsCollection()) || !$address->getQuote()->getPayment()->hasMethodInstance())){            
            return $this;
        }

        $paymentMethod = $address->getQuote()->getPayment()->getMethodInstance();

        if ($paymentMethod->getCode() != 'cielo') {            
            return $this;
        }

        $paymentMethod = Mage::app()->getFrontController()->getRequest()->getParam('payment');
        $parcela = $paymentMethod['cc_parcelas']; // resgata o numero de parcelas escolhido, da session


        $address->setEncargo(0);
        $address->setBaseEncargo(0);
        $payment = Mage::getSingleton('checkout/session')->getQuote()->getPayment();
        $grandTotal = $address->getGrandTotal() > 0 ? $address->getGrandTotal() : array_sum($address->getAllTotalAmounts());
        $totals = Mage::getSingleton('checkout/cart')->getQuote()->getTotals();
        $totalProdutos = $totals['subtotal']->getValue(); 


        if ($totalProdutos > 0 && $paymentMethod['method'] == 'cielo' && $paymentMethod['cc_parcelas'] >= 2) {
            if ((int) $parcela > (int) Mage::getStoreConfig('payment/cielo/parcelas_sem_juros')) {

                $total = $grandTotal;
                $taxa_juros = Mage::getStoreConfig('payment/cielo/taxa_juros');
                $n = $parcela;

                for ($i = 0; $i < $n; $i++) {
                    $total *= 1 + ($taxa_juros / 100);
                }

                $encargo = $total - $grandTotal;

                $address->setEncargo($encargo);
                $address->setBaseEncargo($encargo);
                $address->setGrandTotal($address->getGrandTotal() + $address->getEncargo());
                //$address->setBaseGrandTotal($address->getGrandTotal() + $address->getBaseEncargo());
                $address->setBaseGrandTotal($address->getGrandTotal());
            }
        }

        return $this;
    }

    /**
     * Used each time when totals are displayed
     * 
     * @param Mage_Sales_Model_Quote_Address $address
     * @return Your_Module_Model_Total_Custom
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address) {
        // Display total only if it is not zero
//				echo var_dump($address->getEncargo());
        if ($address->getEncargo() > 0) {


            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => 'Encargos Financeiros',
                'value' => $address->getEncargo()
            ));
        }
    }

}

