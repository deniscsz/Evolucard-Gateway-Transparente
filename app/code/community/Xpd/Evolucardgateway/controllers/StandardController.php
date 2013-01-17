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
class Xpd_Evolucardgateway_StandardController extends Mage_Core_Controller_Front_Action {

    /**
     * Send expire header to ajax response
     *
     */
    protected function _expireAjax() {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with paypal strandard order transaction information
     *
     * @return Mage_Paypal_Model_Standard
     */
    public function getStandard() {
        return Mage::getSingleton('evolucardgateway/standard');
    }
    
    protected function _consultaEvolucard() {
        
        $evolucard = $this->getStandard();
        $merchantCode = Mage::getStoreConfig('payment/evolucardgateway/evocode');
        
        $mobileCc = Mage::getSingleton('core/session')->getDdiCel();
        $mobileAc = Mage::getSingleton('core/session')->getDddCel();
        $mobileNb = Mage::getSingleton('core/session')->getNumberCel();
        
        if($evolucard->ambiente == "0") {
            $url = 'https://www.evolucard.com.br/postServiceTest/getConsumer';
        }
        else {
            $url = 'https://www.evolucard.com.br/postService/getConsumer';
        }
        
        $fields = array(
            'merchantCode' => urlencode($merchantCode),
            'mobileCc' => urlencode($mobileCc),
            'mobileAc' => urlencode($mobileAc),
            'mobileNb' => urlencode($mobileNb)
        );
        
        $curlAdapter = new Varien_Http_Adapter_Curl();
        $curlAdapter->setConfig(array('timeout'   => 20));
        //$curlAdapter->connect(your_host[, opt_port, opt_secure]);
        $curlAdapter->write(Zend_Http_Client::POST, $url, '1.1', array(), $fields);
        $resposta = $curlAdapter->read();
        $retorno = substr($resposta,strpos($resposta, "\r\n\r\n"));
        $curlAdapter->close();
        
//        echo '<br/>';
//        echo '<br/>';
//        
//        var_dump($resposta);
//        
//        echo '<br/>';
//        echo '<br/>';
        
        if(function_exists('json_decode')) {
            $json_php = json_decode($retorno);
            if(isset($json_php->{'code'})) {
                if($json_php->{'code'} == 'EV000' && $json_php->{'isConsumer'} == 'Y') {
                    $evolucard->log('True para consulta');
//                    print('True para consulta<br/>');
                    return true;
                }
                else {
                    if($json_php->{'code'} == 'EV000') {
                        $evolucard->log('False para consulta');
//                        print('False para consulta<br/>');
                        return false;
                    }
                    else {
                        $evolucard->log('Null para consulta '. $json_php->{'code'});
//                        print('Null para consulta<br/>');
                        return null;
                    }
                }
            }
        }
        else {
            $evolucard->log('[ Function Json_Decode does not exist! ]');
            return null;
        }
    }

    /**
     * When a customer chooses Paypal on Checkout/Payment page
     *
     */
    public function redirectAction() {
        $session = Mage::getSingleton('checkout/session');
        $evolucard = $this->getStandard();
        $order = $evolucard->getOrder();
        
        $evolucard->log('Iniciando Processamento');
        $evolucard->log('Order: '.$order->getIncrementId());
        
        if ($order->getId()) {
            if(!$order->getEmailSent()) {
            	$order->sendNewOrderEmail();
    			$order->setEmailSent(true);
    			$order->save();
                $evolucard->log("[ Order Email Sent ]");
            }
        }

        $payment = $order->getPayment();
        Mage::register('current_order',$order);
        
        $bandeiras = array('VI' => 'visa', 'MC' => 'mastercard');
        
        $evolucard->ambiente = Mage::getStoreConfig('payment/evolucardgateway/environment');

        $evolucard->formaPagamentoBandeira = $payment->getData('cc_type');
        $additionaldata = unserialize($payment->getData('additional_data'));

        $evolucard->formaPagamentoProduto = Mage::getStoreConfig('payment/evolucardgateway/parcelamento') == 2 ? 'M' : 'A';
        if ($additionaldata["cc_parcelas"] > 1) {
            $evolucard->formaPagamentoParcelas = $additionaldata["cc_parcelas"];
        } else {
            $evolucard->formaPagamentoParcelas = 1;
        }
        
        $create_account = $this->_consultaEvolucard();
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        
        $continua = 0;
        if($create_account) {
            $customerData = $evolucard->buildDataToPost($customer,$order,$payment,true);
            $continua = 1;
        }
        else {
            if($create_account == false) {
                $customerData = $evolucard->buildDataToPost($customer,$order,$payment,false);
                $continua = 2;
            }
            else {
                $evolucard->log('[ Evolucard returned NULL ]');
            }
        }
        
        if($continua > 0) {
            if($evolucard->ambiente == "0") {
                $url = 'https://www.evolucard.com.br/postServiceTest/newConsumerAndTransaction';
            }
            else {
                $url = 'https://www.evolucard.com.br/postService/newConsumerAndTransaction';
            }
            
            $curlAdapter = new Varien_Http_Adapter_Curl();
            $curlAdapter->setConfig(array('timeout'   => 20));
            //$curlAdapter->connect(your_host[, opt_port, opt_secure]);
            $curlAdapter->write(Zend_Http_Client::POST, $url, '1.1', array(), $customerData);
            $resposta = $curlAdapter->read();
            $retorno = substr($resposta,strpos($resposta, "\r\n\r\n"));
            $curlAdapter->close();
            
            if(function_exists('json_decode')) {
                $json_php = json_decode($retorno);
                if(isset($json_php->{'code'})) {
                    if($json_php->{'code'} == 'EV000') {
                        if(isset($json_php->{'transactionNumberEvc'})) {
                            $evolucard->setTransactionIdEvo($json_php->{'transactionNumberEvc'});
                            $order->getPayment()->setEvolucardTransactionId(utf8_encode($json_php->{'transactionNumberEvc'}));
                            $order->setEvolucardTransaction(utf8_encode($json_php->{'transactionNumberEvc'}));
                            $flag = true;
                            
//                            echo '<br/>';echo '<br/>';
//                            echo utf8_encode($json_php->{'transactionNumberEvc'});
//                            echo '[ '.$order->getPayment()->getEvolucardTransactionId().' ]';
//                            echo '<br/>';
                            
                            $order->getPayment()->setAuthorizationNumber(utf8_encode($json_php->{'transactionNumberAcq'}));
//                            echo utf8_encode($json_php->{'transactionNumberAcq'});
//                            echo '<br/>';
                            
                            $order->getPayment()->setAcqNumberTransaction(utf8_encode($json_php->{'authorizationNumber'}));
//                            echo utf8_encode($json_php->{'authorizationNumber'});
//                            echo '<br/>';echo '<br/>';
                        }
                        else {
                            $evolucard->log('[ TransactionNumberEvc not found ]');
                            $flag = false;
                        }
//                        echo 'JSON veio certo Sucess<br/>';
                    }
                    else {
                        $evolucard->log('[ Erro Code: '. $json_php->{'code'} .' ]');
                        $flag = false;
//                        echo '<br/><br/>JSON veio certo porem Falhou '. $json_php->{'code'} .'<br/>';
                    }
                }
                else {
                    $evolucard->log('[ Error with Json ]');
                    $flag = false;
//                    echo 'JSON bixado<br/>';
                }
            }
            else {
                $evolucard->log('[ Function Json_Decode does not exist! ]');
            }
            
//            echo '<br/> vai confirmar o cadastro ou nao <br/>';
            
            if($evolucard->ambiente == "0") {
                $url = 'https://www.evolucard.com.br/postServiceTest/updateConsumer';
            }
            else {
                $url = 'https://www.evolucard.com.br/postService/updateConsumer';
            }
            
            $fields = array(
                'merchantCode' => Mage::getStoreConfig('payment/evolucardgateway/evocode'),
                'mobileCc' => Mage::getSingleton('core/session')->getDdiCel(),
                'mobileAc' => Mage::getSingleton('core/session')->getDddCel(),
                'mobileNb' => Mage::getSingleton('core/session')->getNumberCel(),
                'confirmConsumer' => $continua == 1 ? urlencode('Y') : urlencode('N')
            );
            
//            var_dump($fields);
            
            $curlAdapter = new Varien_Http_Adapter_Curl();
            $curlAdapter->setConfig(array('timeout' => 20));
            $curlAdapter->write(Zend_Http_Client::POST, $url, '1.1', array(), $fields);
            $resposta = $curlAdapter->read();
//            echo '<br/><br/>';
//            var_dump($resposta);
//            echo '<br/><br/>';
            
            $retorno = substr($resposta,strpos($resposta, "\r\n\r\n"));
            $curlAdapter->close();
            if(function_exists('json_decode')) {
                $json_php = json_decode($retorno);
                if(isset($json_php->{'code'})) {
                    if($json_php->{'code'} == 'EV000') {
                        $evolucard->log('[ Cadastro Confirmado ou Recusado com sucesso ]');
                        $flag = true;
//                        echo ' Error Sucesso  <br/>';
                    }
                    else {
                        $evolucard->log('[ Code EV070: Error ]');
                        $flag = false;
//                        echo ' Error EV070  <br/>';
                    }
                }
                else {
                    $evolucard->log('[ Error with JSON ]');
                    $flag = false;
//                    echo ' Error with JSON  <br/>';
                }
            }
            else {
                $flag = false;
            }
        }
        
        $payment->setAdditionalData(serialize($additionaldata));
        $payment->save();
        
        if ($flag) {
            $url = Mage::getUrl('checkout/onepage/success');
        } else {
            $url = Mage::getUrl('checkout/onepage/failure');
        }
        
        $session->setRedirectUrl($url);

        $this->getResponse()->setBody($this->getLayout()->createBlock('evolucardgateway/standard_redirect')->toHtml());
        $session->unsQuoteId();
        $session->unsRedirectUrl();
    }

    public function capturaAction() {
        if($this->getRequest()->isPost() && $this->getRequest()->getParam('id') && $this->getRequest()->getParam('status') && Mage::getStoreConfig('payment/evolucardgateway/captura')) {
            $evoId = $this->getRequest()->getParam('id');
            $status = $this->getRequest()->getParam('status');
            
            echo $orderFilter = Mage::getModel('sales/order_payment')->getCollection()
                ->addAttributeToSelect('parent_id')
                ->addAttributeToFilter('evolucard_transaction_id', array('eq' => $evoId))
                ->getLastItem();
            $orderId = $orderFilter->getParentId();
            $order = Mage::getModel('sales/order')->load($orderId);
            
            if($order->getIncrementId()) {
                echo $order->getIncrementId();
                $evolucard = $this->getStandard();
                $evolucard->geraInvoice($order,$status,$evoId);
            }
        }
    }
}