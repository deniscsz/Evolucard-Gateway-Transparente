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
    
    //protected function _getCelNumber() {
//        $order = $this->getOrder();
//        if($order) {
//            $billingAddress = !$order->getIsVirtual() ? $order->getBillingAddress() : null;
//            if($billingAddress) {
//                if($billingAddress->getData("celphone")) {
//                    return $billingAddress->getData("celphone");
//                }
//                else {
//                    if($billingAddress->getData("fax")) {
//                        return $billingAddress->getData("fax");
//                    }
//                    else {
//                        return $billingAddress->getData("telephone");
//                    }
//                }
//            }
//            else {
//                return null;
//            }
//        }
//        else {
//            $telephone = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getTelephone();
//            if($telephone) {
//                return $telephone;
//            }
//            else {
//                $celphone = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getCelphone();
//                if($celphone) {
//                    return $celphone;
//                }
//                else {
//                    $fax = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getFax();
//                    if($fax) {
//                        return $fax;
//                    }
//                    else {
//                        return 0;
//                    }
//                }
//            }
//        }
//    }

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
        
        echo '<br/>';
        echo '<br/>';
        
        var_dump($resposta);
        
        echo '<br/>';
        echo '<br/>';
        
        if(function_exists('json_decode')) {
            $json_php = json_decode($retorno);
            if(isset($json_php->{'code'})) {
                if($json_php->{'code'} == 'EV000' && $json_php->{'isConsumer'} == 'Y') {
                    $evolucard->log('True para consulta');
                    print('True para consulta<br/>');
                    return true;
                }
                else {
                    if($json_php->{'code'} == 'EV000') {
                        $evolucard->log('False para consulta');
                        print('False para consulta<br/>');
                        return false;
                    }
                    else {
                        $evolucard->log('Null para consulta');
                        print('Null para consulta<br/>');
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

        if ($order->getId()) {
            if(!$order->getEmailSent()) {
            	$order->sendNewOrderEmail();
    			$order->setEmailSent(true);
    			$order->save();
                $evolucard->log("[ Order Email Sent ]");
            }
        }

        $payment = $order->getPayment();
        
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
            if($create_account === false) {
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
                            $order->getPayment()->setAuthorizationNumber(utf8_encode($json_php->{'transactionNumberAcq'}));
                            $order->getPayment()->setAcqNumberTransaction(utf8_encode($json_php->{'authorizationNumber'}));
                        }
                        else {
                            $evolucard->log('[ TransactionNumberEvc not found ]');
                        }
                        echo 'JSON veio certo Sucess<br/>';
                    }
                    else {
                        $evolucard->log('[ Erro Code: '. $json_php->{'code'} .' ]');
                        echo '<br/><br/>JSON veio certo porem Falhou '. $json_php->{'code'} .'<br/>';
                    }
                }
                else {
                    $evolucard->log('[ Error with Json ]');
                    echo 'JSON bixado<br/>';
                }
            }
            else {
                $evolucard->log('[ Function Json_Decode does not exist! ]');
            }
            
            echo '<br/> vai confirmar o cadastro ou nao <br/>';
            
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
            
            var_dump($fields);
            
            $curlAdapter = new Varien_Http_Adapter_Curl();
            $curlAdapter->setConfig(array('timeout' => 20));
            $curlAdapter->write(Zend_Http_Client::POST, $url, '1.1', array(), $fields);
            $resposta = $curlAdapter->read();
            echo '<br/><br/>';
            var_dump($resposta);
            echo '<br/><br/>';
            
            $retorno = substr($resposta,strpos($resposta, "\r\n\r\n"));
            $curlAdapter->close();
            if(function_exists('json_decode')) {
                $json_php = json_decode($retorno);
                if(isset($json_php->{'code'})) {
                    if($json_php->{'code'} == 'EV000') {
                        $evolucard->log('[ Cadastro Confirmado ou Recusado com sucesso ]');
                        echo ' Error Sucesso  <br/>';
                    }
                    else {
                        $evolucard->log('[ Code EV070: Error ]');
                        echo ' Error EV070  <br/>';
                    }
                }
                else {
                    echo ' Error with JSON  <br/>';
                    $evolucard->log('[ Error with JSON ]');
                }
            }
        }
        
        /*
        $cielo->capturar = Mage::getStoreConfig('payment/evolucardgateway/captura') ? 'true' : 'false';
        $cielo->autorizar = "3";

        $cielo->dadosPortadorNumero = $payment->decrypt($payment->getCcNumberEnc());
        $cielo->dadosPortadorVal = $payment->getCcExpYear() . str_pad($payment->getCcExpMonth(), 2, "0", STR_PAD_LEFT);

        if (!$additionaldata['cc_cid_enc']) {
            $cielo->dadosPortadorInd = "0";
        } elseif ($bandeiras[$payment->getData('cc_type')] == 'mastercard') {
            $cielo->dadosPortadorInd = "1";
        } else {
            $cielo->dadosPortadorInd = "1";
        }

        $cielo->dadosPortadorCodSeg = $payment->decrypt($additionaldata['cc_cid_enc']);
        $cielo->dadosPedidoNumero = Mage::getSingleton('checkout/session')->getLastRealOrderId();


        $calculaEncargo = $order->getGrandTotal();
        
        $taxa_juros = Mage::getStoreConfig('payment/cielo/taxa_juros');
        $n = $additionaldata["cc_parcelas"];

        for ($i = 0; $i < $n; $i++) {
            $calculaEncargo *= 1 + ($taxa_juros / 100);
        }
		
        $totalEncargo = $calculaEncargo;

        
        $cielo->dadosPedidoValor = number_format($totalEncargo, 2, '', '');

        $cielo->urlRetorno = Mage::getBaseUrl() . 'evolucardgateway/standard/redirect/';
        */
        /*
         * Envia os dados do cliente e requisita do TID, para dar inicio ao processo
         */
        /*$objResposta = $cielo->RequisicaoTid();

        $cielo->tid = $objResposta->tid;
        $cielo->pan = $objResposta->pan;
        $cielo->status = $objResposta->status;
        */
        /*
         * Envia os dados do pedido e do cliente
         */
        /*$objResposta = $cielo->RequisicaoAutorizacaoPortador();
        $additionaldata = unserialize($payment->getData('additional_data'));
        $additionaldata['tid'] = (string) $objResposta->tid;

        if ($objResposta->mensagem) {
            $additionaldata['erro'] = array('codigo' => (string) $objResposta->codigo, 'mensagem' => (string) $objResposta->mensagem);
        }

        $cielo->tid = $objResposta->tid;
        $cielo->pan = $objResposta->pan;
        $cielo->status = $objResposta->status;

        if ($objResposta->mensagem) {
            $additionaldata['erro'] = !isset($additionaldata['erro']) ? array('codigo' => (string) $objResposta->codigo, 'mensagem' => (string) $objResposta->mensagem) : $additionaldata['erro'];
        } else {
            $additionaldata['tid'] = (string) $objResposta->tid;
        }

        $additionaldata['status'] = (string) $objResposta->status;
        if ($objResposta->autorizacao) {
            $additionaldata['autorizacao'] = array(
                'codigo' => (string) $objResposta->autorizacao->codigo,
                'mensagem' => (string) $objResposta->autorizacao->mensagem
            );
        }
        if ($objResposta->captura) {
            $additionaldata['captura'] = array(
                'codigo' => (string) $objResposta->captura->codigo,
                'mensagem' => (string) $objResposta->captura->mensagem
            );
        }
        
        $payment->setAdditionalData(serialize($additionaldata));
        $payment->save();*/
        
        if ($liberaFatura) {
            $url = Mage::getUrl('cielo/standard/success');
            if(Mage::getStoreConfig('payment/evolucardgateway/gerar_invoice') == true) {  
            /* Inicio codigo gera invoice */
				try {
					if(!$order->canInvoice()) {
						Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
					}
					 
					$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
					 
					if (!$invoice->getTotalQty()) {
						Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
					}
					 
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->register();
					$transactionSave = Mage::getModel('core/resource_transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
					 
					$transactionSave->save();
				}
				catch (Mage_Core_Exception $e) {
				 
				}
            /* Fim codigo gera invoice */            	 		
        	}
        } else {
            $url = Mage::getUrl('checkout/onepage/failure');
        }
		
		//die();

        /*$session->setRedirectUrl($url);

        $this->getResponse()->setBody($this->getLayout()->createBlock('evolucardgateway/standard_redirect')->toHtml());
        $session->unsQuoteId();
        $session->unsRedirectUrl();*/
    }

    public function capturaAction() {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $payment = $order->getPayment();
        $additionaldata = unserialize($payment->getData('additional_data'));
        $tid = $additionaldata["tid"];

        $valor = number_format($order->getGrandTotal(), 2, '', '');

        $cielo = Mage::getModel('cielo/cielo');
        //$cielo->ambiente = Mage::getStoreConfig('payment/cielo/environment');
        $objResposta = $cielo->RequisicaoCaptura($tid, $loja, $chave, $valor, null);

        if ($objResposta->captura) {
            $additionaldata['captura'] = array(
                'codigo' => (string) $objResposta->captura->codigo,
                'mensagem' => (string) $objResposta->captura->mensagem
            );

            $payment->setAdditionalData(serialize($additionaldata));
            $payment->save();

            $codigo = (string) $objResposta->captura->codigo;
            $mensagem = (string) $objResposta->captura->mensagem;
        }
        else {
            if ($objResposta->codigo) {
                $codigo = (string) $objResposta->codigo;
                $mensagem = (string) $objResposta->mensagem;
            }
        }


        /*$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt" lang="pt"><head><title>Capturar Transação</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' .
                '<body onunload="opener.location.reload();">' .
                '<h1>Captura Transa&ccedil;&atilde;o</h1><br /><br />' .
                'c&oacute;digo: ' . $codigo . '<br />' .
                'mensagem: ' . $mensagem . '<br />' .
                '<br /><br /><br /><br />' .
                '<button onclick="window.close();">Fechar Janela</button>';

        $this->getResponse()->setBody($body);*/
    }
    
    public function cancelamentoAction() {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $payment = $order->getPayment();
        $additionaldata = unserialize($payment->getData('additional_data'));
        $tid = $additionaldata["tid"];

        /* Identifica o ambiente , se teste "0", se produção "1" */

        if (Mage::getStoreConfig('payment/cielo/environment') == 0) {
            $loja = "1006993069";
            $chave = "25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f3";
            $urlEnviroment = "https://qasecommerce.cielo.com.br/servicos/ecommwsec.do";
        } else {
            $loja = Mage::getStoreConfig('payment/cielo/merchant_id');
            $chave = Mage::getStoreConfig('payment/cielo/merchant_key');
            $urlEnviroment = "https://ecommerce.cbmp.com.br/servicos/ecommwsec.do";
        }

        $cielo = Mage::getModel('cielo/cielo');
        
        $objResposta = $cielo->RequisicaoCancelamento($tid, $loja, $chave);


        if ($objResposta->cancelamento) {
            $additionaldata['cancelamento'] = array(
                'codigo' => (string) $objResposta->cancelamento->codigo,
                'mensagem' => (string) $objResposta->cancelamento->mensagem
            );

            $payment->setAdditionalData(serialize($additionaldata));
            $payment->save();

            $codigo = (string) $objResposta->cancelamento->codigo;
            $mensagem = (string) $objResposta->cancelamento->mensagem;
        } else {
            if ($objResposta->codigo) {
                $codigo = (string) $objResposta->codigo;
                $mensagem = (string) $objResposta->mensagem;
            }
        }


        $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt" lang="pt"><head><title>Capturar Transação</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' .
                '<body onunload="opener.location.reload();">' .
                '<h1>Cancela Transa&ccedil;&atilde;o</h1><br /><br />' .
                'c&oacute;digo: ' . $codigo . '<br />' .
                'mensagem: ' . $mensagem . '<br />' .
                '<br /><br /><br /><br />' .
                '<button onclick="window.close();">Fechar Janela</button>';

        $this->getResponse()->setBody($body);
    }
    
    public function consultaAction() {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $payment = $order->getPayment();
        $additionaldata = unserialize($payment->getData('additional_data'));
        $tid = $additionaldata["tid"];
        
        /* Identifica o ambiente , se teste "0", se produção "1" */

        if (Mage::getStoreConfig('payment/cielo/environment') == 0) {
            $loja = "1006993069";
            $chave = "25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f3";
            $urlEnviroment = "https://qasecommerce.cielo.com.br/servicos/ecommwsec.do";
        } else {
            $loja = Mage::getStoreConfig('payment/cielo/merchant_id');
            $chave = Mage::getStoreConfig('payment/cielo/merchant_key');
            $urlEnviroment = "https://ecommerce.cbmp.com.br/servicos/ecommwsec.do";
        }

        $cielo = Mage::getModel('cielo/cielo');
        
        $objResposta = $cielo->RequisicaoConsulta($tid, $loja, $chave);

        $body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pt" lang="pt"><head><title>Consulta Transação</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' .
                '<body onunload="opener.location.reload();">' .
                '<h1>Consulta Transa&ccedil;&atilde;o</h1>' .
                '<textarea cols="73" rows="40" readonly="readonly"> ' . htmlentities($objResposta->asXML()) . '</textarea><br />' .
                '<br />' .
                '<button onclick="window.close();">Fechar Janela</button>';

        $this->getResponse()->setBody($body);
    }



    public function successAction() {
        $session = Mage::getSingleton('checkout/session');
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }

}

