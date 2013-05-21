<?php
/**
 * Evolucard
 *
 * @category   Payments
 * @package    Xpd_Evolucardgateway
 * @license    OSL v3.0
 */
class Xpd_Evolucardgateway_Model_Standard extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'evolucardgateway';
    protected $_formBlockType = 'evolucardgateway/form_cc';
    protected $_infoBlockType = 'evolucardgateway/info';
    protected $_isInitializeNeeded = true;
    
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = true;
    protected $_canUseCheckout = true;
    protected $_order;
    
    public $formaPagamentoBandeira;
    public $formaPagamentoProduto;
    public $formaPagamentoParcelas;
    public $ambiente = 1;
    
    /**
     *  Get order
     *
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder() {
        if ($this->_order == null) {
            $this->_order = Mage::getModel('sales/order')->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        }
        return $this->_order;
    }

    public function assignData($data) {
        $details = array();
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $additionaldata = array('cc_parcelas' => $data->getCcParcelas(), 'cc_cid_enc' => $info->encrypt($data->getCcCid()));
        $info->setAdditionalData(serialize($additionaldata));
        $info->setCcType($data->getCcType());
        $info->setCcOwner($data->getCcOwner());
        $info->setCcExpMonth($data->getCcExpMonth());
        $info->setCcExpYear($data->getCcExpYear());
        $info->setCcNumberEnc($info->encrypt($data->getCcNumber()));
        $info->setCcCidEnc($info->encrypt($data->getCcCid()));
        $info->setCcLast4(substr($data->getCcNumber(), -4));
        
        Mage::getSingleton('core/session')->setDdiCel($data->getDdiCel());
        Mage::getSingleton('core/session')->setDddCel($data->getDddCel());
        Mage::getSingleton('core/session')->setNumberCel($data->getNumberCel());
        Mage::getSingleton('core/session')->setDdiTel($data->getDdiTel());
        Mage::getSingleton('core/session')->setDddTel($data->getDddTel());
        Mage::getSingleton('core/session')->setNumberTel($data->getNumberTel());
        Mage::getSingleton('core/session')->setCreateAccount($data->getCreateAccount());
        
        return $this;
    }

    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('evolucardgateway/standard/redirect', array('_secure' => true));
    }

    /**
     * log
     * 
     * Registra log de eventos/erros.
     * 
     * @param string $message
     * @param integer $level
     * @param string $file
     * @param bool $forceLog
     */
    public function log($message, $level = null, $file = 'evolucard.log', $forceLog = false) {
        Mage::log("Evolucard - " . $message, $level, 'evolucard.log', $forceLog);
    }
    
    public function geraInvoice($order,$status,$id) {
        
        if ($status == 'APR') {
            if ($order->canUnhold()) {
        	    $order->unhold();
        	}
            if ($order->canInvoice()) {
                $changeTo = Mage_Sales_Model_Order::STATE_PROCESSING;
                
                Mage::getSingleton("core/session")->setInvoiceMail(1);
                
                $invoice = $order->prepareInvoice();
                $invoice->register()->pay();
                $invoice_msg = utf8_encode(sprintf('Pagamento confirmado. Transa&ccedil;&atilde;o Evolucard: %s', $transacaoID));
                $invoice->addComment($invoice_msg, true);
                $invoice->sendEmail(true, $invoice_msg);
                $invoice->setEmailSent(true);
                //$this->log("Email Fatura Enviado");
            
                Mage::getModel('core/resource_transaction')
                   ->addObject($invoice)
                   ->addObject($invoice->getOrder())
                   ->save();
                $comment = utf8_encode(sprintf('Fatura #%s criada.', $invoice->getIncrementId()));
                $order->setState($changeTo, true, $comment, $notified = true);
                $order->save();                        
                //$this->log("[ Fatura criada ]");
                Mage::getSingleton("core/session")->setInvoiceMail(0);
                Mage::getSingleton("core/session")->clear();
            }
            else {
                // Lógica para quando a fatura não puder ser criada
                //$this->log("Fatura nao pode ser criada");
            }
        }
        else {
            // Pedido cancelado
            if ($status == 'REP') {
                if ($order->canUnhold()) {
    	           $order->unhold();
                }
                if ($order->canCancel()) {
                    $order_msg = "Pagamento Cancelado.";
            		$changeTo = Mage_Sales_Model_Order::STATE_CANCELED;
            		$order->getPayment()->setMessage($order_msg);
            		$order->cancel();
                    $order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
                    //$this->log("Ordem cancelada ".$order->getRealOrderId());
                }
            }
        }
    }
    
    public function buildDataToPost($customer,$order,$payment,$create_account = true) {
        $fields = Array();
        
        $fields['merchantCode'] = Mage::getStoreConfig('payment/evolucardgateway/evocode');
        $fields['docNumber'] = Mage::helper('evolucardgateway')->convertOrderId($order->getId());
        
        $fields['consumer.mobileAc'] = Mage::getSingleton('core/session')->getDddCel();
        $fields['consumer.mobileNb'] = Mage::getSingleton('core/session')->getNumberCel();
        
        if($fields['consumer.mobileNb'] && $fields['consumer.mobileAc']) {
            $fields['consumer.mobileCc'] = Mage::getSingleton('core/session')->getDdiCel();
            $fields['consumer.mobilePhoneOperator'] = 1;
        }
        else {
            $fields['consumer.mobileCc'] = $fields['consumer.mobileAc'];
        }
        
        //if(!$create_account && !$fields['consumer.mobileNb']){
//            $fields['consumer.mobileCc'] = '';
//            $fields['consumer.mobileAc'] = '';
//            $fields['consumer.mobileNb'] = '';
//            //$fields['consumer.mobilePhoneOperator'] = '';
//        }
        
        //echo $order->getCustomerDob();
        $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime($order->getCustomerDob())) + 15000;
        $fields['consumer.birthDate'] = date('Y-m-d', $dateTimestamp);
        
        switch($payment->getData('cc_type')) {
            case 'visa': $fields['consumer.cardPaymentBrand'] = 1; break;
            case 'mastercard': $fields['consumer.cardPaymentBrand'] = 2; break;
            case 'amex': $fields['consumer.cardPaymentBrand'] = 3; break;
            case 'diners': $fields['consumer.cardPaymentBrand'] = 4; break;
            case 'elo': $fields['consumer.cardPaymentBrand'] = 6; break; 
        }
        
        $fields['consumer.cardHolderName'] = $payment->getCcOwner();
        $fields['consumer.cardNumber'] = $payment->decrypt($payment->getCcNumberEnc());
        $fields['consumer.cardExpirationDate'] = str_pad($payment->getCcExpMonth(), 2, "0", STR_PAD_LEFT) . '/' . $payment->getCcExpYear();
        $additionaldata = unserialize($payment->getData('additional_data'));
        $fields['consumer.cardSecurityCode'] = $payment->decrypt($additionaldata['cc_cid_enc']);
        $fields['value'] = number_format($order->getGrandTotal(), 2, '.', '');
        $fields['numberPayment'] = Mage::getSingleton('core/session')->getNumparevo();
    	
		if(Mage::getStoreConfig('payment/evolucardgateway/evocode') == "2")
			$fields['installmentResponsible'] = 'M';
		else
			$fields['installmentResponsible'] = 'A';
        
        $fields['consumer.phoneAc'] = Mage::getSingleton('core/session')->getDddTel();
        $fields['consumer.phoneNb'] = Mage::getSingleton('core/session')->getNumberTel();
        if($fields['consumer.phoneNb'] && $fields['consumer.phoneAc']) {
            $fields['consumer.phoneCc'] = Mage::getSingleton('core/session')->getDdiTel();
        }
        else {
            $fields['consumer.phoneCc'] = $fields['consumer.phoneNb'];
        }
//            $fields['consumer.mobileCc'] = '';
//            $fields['consumer.mobileAc'] = '';
//            $fields['consumer.mobileNb'] = '';
//        }
//      $fields['consumer.mobilePhoneOperator'] = 1;
        
        $billingAddress = !$order->getIsVirtual() ? $order->getBillingAddress() : null;
        if($billingAddress) {
            $fields['consumer.address.zipcode'] = str_replace('-','',str_replace('.','',$billingAddress->getData("postcode")));
            //Todos os Address preenchidos
            if($billingAddress->getStreet(1) && $billingAddress->getStreet(2) && $billingAddress->getStreet(3) && $billingAddress->getStreet(4)) {
                $fields['consumer.address.address'] = $billingAddress->getStreet(1);
                $fields['consumer.address.addressNumber'] = $billingAddress->getStreet(2);
                $fields['consumer.address.addComplement'] = $billingAddress->getStreet(3);
                $fields['consumer.address.district'] = $billingAddress->getStreet(4);
            }
            else {
                if($billingAddress->getStreet(1) && $billingAddress->getStreet(2) && $billingAddress->getStreet(3) && !$billingAddress->getStreet(4)) {
                    $fields['consumer.address.address'] = $billingAddress->getStreet(1);
                    $fields['consumer.address.addComplement'] = $billingAddress->getStreet(2);
                    $fields['consumer.address.addressNumber'] = $billingAddress->getStreet(2);
                    $fields['consumer.address.district'] = $billingAddress->getStreet(3);
                }
                else {
                    if($billingAddress->getStreet(1) && $billingAddress->getStreet(2) && !$billingAddress->getStreet(3) && !$billingAddress->getStreet(4)) {
                        $fields['consumer.address.address'] = $billingAddress->getStreet(1);
                        $fields['consumer.address.addComplement'] = $billingAddress->getStreet(2);
                        $fields['consumer.address.addressNumber'] = $billingAddress->getStreet(2);
                        $fields['consumer.address.district'] = $billingAddress->getStreet(2);
                    }
                    else {
                        $fields['consumer.address.address'] = $billingAddress->getStreet(1);
                        $fields['consumer.address.addComplement'] = $billingAddress->getStreet(2);
                        $fields['consumer.address.addressNumber'] = $billingAddress->getStreet(2);
                        $fields['consumer.address.district'] = $billingAddress->getStreet(2);
                    }
                }
            }
            $fields['consumer.address.city'] = $billingAddress->getData("city");
            
            if($billingAddress->getRegionCode()) {
                switch($billingAddress->getRegionCode()) {
                    case 'AC': $fields['consumer.address.state'] = 'Acre'; break;
                    case 'AL': $fields['consumer.address.state'] = 'Alagoas'; break;
                    case 'AP': $fields['consumer.address.state'] = 'Amapá'; break;
                    case 'BA': $fields['consumer.address.state'] = 'Bahia'; break;
                    case 'CE': $fields['consumer.address.state'] = 'Ceará'; break;
                    case 'DF': $fields['consumer.address.state'] = 'Distrito Federal'; break;
                    case 'ES': $fields['consumer.address.state'] = 'Espírito Santo'; break;
                    case 'GO': $fields['consumer.address.state'] = 'Goais'; break;
                    case 'MA': $fields['consumer.address.state'] = 'Maranhão'; break;
                    case 'MT': $fields['consumer.address.state'] = 'Mato Grosso'; break;
                    case 'MS': $fields['consumer.address.state'] = 'Mato Grosso do Sul'; break;
                    case 'MG': $fields['consumer.address.state'] = 'Minas Gerais'; break;
                    case 'PA': $fields['consumer.address.state'] = 'Pará'; break;
                    case 'PB': $fields['consumer.address.state'] = 'Paraíba'; break;
                    case 'PR': $fields['consumer.address.state'] = 'Paraná'; break;
                    case 'PE': $fields['consumer.address.state'] = 'Pernambuco'; break;
                    case 'PI': $fields['consumer.address.state'] = 'Piauí'; break;
                    case 'RJ': $fields['consumer.address.state'] = 'Rio de Janeiro'; break;
                    case 'RN': $fields['consumer.address.state'] = 'Rio Grande do Norte'; break;
                    case 'RS': $fields['consumer.address.state'] = 'Rio Grande do Sul'; break;
                    case 'RO': $fields['consumer.address.state'] = 'Rondônia'; break;
                    case 'RR': $fields['consumer.address.state'] = 'Roraima'; break;
                    case 'SC': $fields['consumer.address.state'] = 'Santa Catarina'; break;
                    case 'SP': $fields['consumer.address.state'] = 'São Paulo'; break;
                    case 'SE': $fields['consumer.address.state'] = 'Sergipe'; break;
                    case 'TO': $fields['consumer.address.state'] = 'Tocantins'; break;
                    default: $fields['consumer.address.state'] = $billingAddress->getRegionCode();
                }
            }
        }
        
        if($consumer) {
            $fields['consumer.name'] = $customer->getName();
            
            if($customer->getCpfcnpj() || $billingAddress->getCpfcnpj()) {
                $cpf0 = $customer->getCpfcnpj() ? $customer->getCpfcnpj() : $billingAddress->getCpfcnpj();
                $cpf = str_replace('-','',str_replace('.','',$cpf0));
                $fields['consumer.document'] = $cpf;
            }
            else {
                $cpf = str_replace('-','',str_replace('.','',$customer->getTaxvat()));
                $fields['consumer.document'] = $cpf;
            }
        
            $fields['consumer.email'] = $customer->getEmail();
            
            $currentDate = Mage::app()->getLocale()->date()->toString('YYYY-MM-dd');
            $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime($currentDate)) - 5184000;
            //echo $dataForFilter = date('Y-m-d', $dateTimestamp);
            
            $ordersGood = Mage::getModel('sales/order')->getCollection()
                                ->addFieldToFilter('customer_id', array('eq' => array($customer->getId())))
                                ->addFieldToFilter('status','complete')
                                ->addAttributeToFilter('created_at', array('gteq' => $dataForFilter));
            $ordersComplete = $ordersGood->getSize();
            
            $ordersCanceled = Mage::getModel('sales/order')->getCollection()
                                ->addFieldToFilter('customer_id', array('eq' => array($customer->getId())))
                                ->addFieldToFilter('status','fraud')
                                ->addAttributeToFilter('created_at', array('gteq' => $dataForFilter));
            $ordersCanceled = $ordersCanceled->getSize();
            
            if($ordersCanceled <= 0 && $ordersComplete > 0) {
                $fields['consumer.knowByMerchant'] = 'Y';
            }
            else {
                $fields['consumer.knowByMerchant'] = 'N';
            }
        }
        else {
            $fields['consumer.name'] = $billingAddress->getFirstname() . $billingAddress->getLastname();
            
            if($billingAddress->getCpfcnpj()) {
                $cpf = str_replace('-','',str_replace('.','',$billingAddress->getCpfcnpj()));
                $fields['consumer.document'] = $cpf;
            }
            else {
                $cpf = str_replace('-','',str_replace('.','',$order->getCustomerTaxvat()));
                $fields['consumer.document'] = $cpf;
            }
        
            $fields['consumer.email'] = $order->getCustomerEmail();
            
            $fields['consumer.knowByMerchant'] = 'N';
        }
        
        $items = $order->getAllVisibleItems();
        $count = 0;
        foreach ($items as $item) {
            $fields['productList'][$count]['productCode'] = $item->getSku();
            $fields['productList'][$count]['productDescription'] = $item->getName();
            $fields['productList'][$count]['productQuantity'] = $item->getQtyOrdered(); 
            $count += 1;
        }
                
        return $fields;
    }
}
