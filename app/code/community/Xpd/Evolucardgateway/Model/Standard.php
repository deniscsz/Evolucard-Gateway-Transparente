<?php

class Xpd_Evolucardgateway_Model_Standard extends Mage_Payment_Model_Method_Abstract {

    protected $_code = 'evolucardgateway';
    protected $_formBlockType = 'evolucardgateway/form_cc';
    protected $_infoBlockType = 'evolucardgateway/info';
    protected $_isInitializeNeeded = true;
    
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = true;
    protected $_canUseCheckout = true;



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

}

