<?php 

$installer = $this;

$installer->startSetup();

$installer->addAttribute('order_payment', 'evolucard_transaction_id', array());
$installer->addAttribute('order_payment', 'evolucard_order_id', array());
$installer->addAttribute('order_payment', 'acq_transaction_number', array());
$installer->addAttribute('order_payment', 'authorization_number', array());

$installer->endSetup();