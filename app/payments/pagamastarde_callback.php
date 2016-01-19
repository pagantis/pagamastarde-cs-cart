<?php

define('AREA', 'C');
define('SKIP_SESSION_VALIDATION', true);
require './../../init.php';

$json = file_get_contents('php://input');
$temp = json_decode($json,true);

$data = $temp['data'];
$order_id = $data['order_id'];
$order_info = fn_get_order_info($order_id);
$payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
$processor_data = fn_get_payment_method_data($payment_id);

if ( $processor_data['processor_params']['pagamastarde_environment']){
  $secret_key = $processor_data['processor_params']['pagamastarde_real_secret_key'];
}else{
  $secret_key = $processor_data['processor_params']['pagamastarde_test_secret_key'];
}

$signature_check = sha1($secret_key.$temp['account_id'].$temp['api_version'].$temp['event'].$temp['data']['id']);
if ($signature_check != $temp['signature'] ){
  //hack detected
  die( '<b>PagaMasTarde</b>: hack detected' );
}


switch ($temp['event']) {
    case 'charge.created':
        $pp_response['order_status'] = 'P';
  			$pp_response["reason_text"] = '';
  			$pp_response["transaction_id"] = $order_id;
  		  break;
    case 'charge.failed':
        $pp_response['order_status'] = 'F';
        $pp_response["reason_text"] = 'Callback received. Payment Failed. Order ID : ' . $order_id;
        $pp_response["transaction_id"] = $order_id;
        break;
}


fn_finish_payment($order_id, $pp_response);
echo "OK";
?>
