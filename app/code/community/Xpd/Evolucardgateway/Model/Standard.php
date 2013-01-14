<?php

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
    
    public function buildDataToPost($customer,$order,$payment,$create_account = true) {
        $fields = Array();
        
        $fields['merchantCode'] = Mage::getStoreConfig('payment/evolucardgateway/evocode');
        $fields['docNumber'] = Mage::helper('evolucardgateway')->convertOrderId($order->getId());
        $fields['consumer.name'] = $customer->getName();
        
        $fields['consumer.mobileCc'] = Mage::getSingleton('core/session')->getDdiCel();
        $fields['consumer.mobileAc'] = Mage::getSingleton('core/session')->getDddCel();
        $fields['consumer.mobileNb'] = Mage::getSingleton('core/session')->getNumberCel();
        echo $order->getCustomerDob();
        $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime($order->getCustomerDob())) + 15000;
        $fields['consumer.birthDate'] = $dataForFilter = date('Y-m-d', $dateTimestamp);
        
        if($customer->getCpfcnpj()) {
            $cpf = str_replace('-','',str_replace('.','',$customer->getCpfcnpj()));
            $fields['consumer.document'] = $cpf;
        }
        else {
            $cpf = str_replace('-','',str_replace('.','',$customer->getTaxvat()));
            $fields['consumer.document'] = $cpf;
        }
        
        $fields['consumer.email'] = $customer->getEmail();
        //$fields['consumer.ip'] = Mage::helper('core/http')->getRemoteAddr(true);
        
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
        $fields['numberPayment'] = $this->formaPagamentoParcelas;
        $fields['installmentResponsible'] = $this->formaPagamentoProduto;
        $fields['consumer.phoneCc'] = Mage::getSingleton('core/session')->getDdiTel();
        $fields['consumer.phoneAc'] = Mage::getSingleton('core/session')->getDddTel();
        $fields['consumer.phoneNb'] = Mage::getSingleton('core/session')->getNumberTel();
        $fields['consumer.mobilePhoneOperator'] = 1;
        
        if($order) {
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
                $fields['consumer.address.state'] = $billingAddress->getRegionCode();
            }
        }
        
        $currentDate = Mage::app()->getLocale()->date()->toString('YYYY-MM-dd');
        $dateTimestamp = Mage::getModel('core/date')->timestamp(strtotime($currentDate)) - 5184000;
        echo $dataForFilter = date('Y-m-d', $dateTimestamp);
        
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
        
        $items = $order->getAllVisibleItems();
        $count = 0;
        foreach ($items as $item) {
            $fields['productList'][$count]['productCode'] = $item->getSku();
            $fields['productList'][$count]['productDescription'] = $item->getName();
            $fields['productList'][$count]['productQuantity'] = $item->getQtyOrdered(); 
            $count += 1;
        }
        
        var_dump($fields);
        
        return $fields;
    }
}