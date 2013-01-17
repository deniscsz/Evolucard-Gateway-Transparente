<?php 

$installer = $this;

$installer->startSetup();

$installer->addAttribute('order_payment', 'evolucard_transaction_id', array());
$installer->addAttribute('order_payment', 'evolucard_order_id', array());
$installer->addAttribute('order_payment', 'acq_transaction_number', array());
$installer->addAttribute('order_payment', 'authorization_number', array());

$attribute  = array(
        'type'          => 'text',
        'backend_type'  => 'text',
        'frontend_input' => 'text',
        'is_user_defined' => true,
        'label'         => 'Evolucard Transaction',
        'visible'       => true,
        'required'      => false,
        'user_defined'  => false,   
        'searchable'    => false,
        'filterable'    => true,
        'comparable'    => true,
        'default'       => ''
);
$installer->addAttribute('order', 'evolucard_transaction', $attribute);

$installer->endSetup();