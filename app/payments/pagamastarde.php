<?php

use Tygh\Registry;

if (!defined('AREA')) {
    die('Access denied');
}

require_once(Registry::get('config.dir.payments') . 'pagamastarde_files/pagamastarde.functions.php');

//URL_OK ENDPOINT
if (defined('PAYMENT_NOTIFICATION') && $mode == 'notify') {
    $order_id = $_REQUEST['order_id'];
    check_notification($order_id);
    fn_order_placement_routines('route', $order_id);
    exit;
}

//NOTIFICATION ENDPOINT
if (defined('PAYMENT_NOTIFICATION') && $mode == 'callback') {
    $json = file_get_contents('php://input');
    $temp = json_decode($json, true);

    $response = callback($temp);
    header("HTTP/1.1 ".$response['code'], true, $response['code']);
    header('Content-Type: application/json', true);
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
    exit;
}

//CHECKOUT PROCESS
$form_fields = get_orders_fields($order_info, $processor_data);
$payment_url='https://pmt.pagantis.com/v1/installments';

$fields='';
foreach ($form_fields as $key => $value) {
    $fields.="<input type='hidden' name='$key' value='$value' />";
}

$msg = fn_get_lang_var('text_cc_processor_connection');
$msg = str_replace('[processor]', 'pagamastarde', $msg);

echo <<<EOT
<html>
  <body onLoad="document.process.submit();">
    <form method='post' action="{$payment_url}" name="process">
      {$fields}
    </form>
    <div align=center>{$msg}</div>
  </body>
</html>
EOT;

exit;
