<?php
//Auth : Senthil.R (senthil1975@gmail.com)
use Tygh\Registry;

if ( !defined('AREA') ) { die('Access denied'); }

if (defined('PAYMENT_NOTIFICATION')) {
    if ($mode == 'notify') {
	$order_id = $_REQUEST['order_id'];

    fn_order_placement_routines('route', $order_id);
}
}
else{

    $order_id = $order_info['order_id'];
    if ( $processor_data['processor_params']['pagamastarde_environment']){
      $account_id = $processor_data['processor_params']['pagamastarde_real_public_key'];
      $secret_key = $processor_data['processor_params']['pagamastarde_real_secret_key'];
    }else{
      $account_id = $processor_data['processor_params']['pagamastarde_test_public_key'];
      $secret_key = $processor_data['processor_params']['pagamastarde_test_secret_key'];
    }
		if ( $processor_data['processor_params']['pagamastarde_discount'] ){
      $discount = 'true';
    }else{
      $discount = 'false';
    }


		$state = $order_info['b_state_descr'];
		if (empty($state)) $state_val = '';
		else $state_val = $state;

	    $nok_url = fn_url('checkout.checkout');
	    $callback_url = Registry::get('config.http_location') . '/app/payments/pagamastarde_callback.php';
//		$ipn_url = fn_url('/app/payments/ecomcharge_callback.php')

    $payment_url='https://pmt.pagantis.com/v1/installments';
		$ok_url = fn_url("payment_notification.notify?payment=pagamastarde&order_id=$order_id&transmode=success", AREA, 'http');
		$fail_url = fn_url("payment_notification.notify?payment=pagamastarde&order_id=$order_id&transmode=fail", AREA, 'http');
		$locale = CART_LANGUAGE;
		$currency = $order_info['secondary_currency'];
		$amount = $order_info['total']*100;
		$description = 'Order #'.$order_id;
    $string = $secret_key
        . $account_id
        . $order_id
        . $amount
        . $currency
        . $ok_url
        . $nok_url
        . $callback_url
        . $discount;
    $signature = sha1($string);
    $signature = hash('sha512',$string);
    $full_name=$order_info[b_firstname].' '.$order_info[b_lastname];

    echo <<<EOT
    <html>
    <body onLoad="document.process.submit();">
     <form method='post' action="{$payment_url}" name="process">
      <input type='hidden' name="account_id" value="{$account_id}" />
      <input type='hidden' name="order_id" value="{$order_id}" />
      <input type='hidden' name="amount" value="{$amount}" />
      <input type='hidden' name="description" value="{$description}" />
      <input type='hidden' name="currency" value="{$currency}" />
      <input type='hidden' name="full_name" value="{$full_name}" />
      <input type='hidden' name="address[street]" value="{$order_info[b_address]}" />
      <input type='hidden' name="address[city]" value="{$order_info[b_city]}" />
      <input type='hidden' name="address[province]" value="{$order_info[b_state_descr]}" />
      <input type='hidden' name="address[zipcode]" value="{$order_info[b_zipcode]}" />
      <input type='hidden' name="shipping[street]" value="{$order_info[s_address]}" />
      <input type='hidden' name="shipping[city]" value="{$order_info[s_city]}" />
      <input type='hidden' name="shipping[province]" value="{$order_info[s_state_descr]}" />
      <input type='hidden' name="shipping[zipcode]" value="{$order_info[s_zipcode]}" />
      <input type='hidden' name="phone" value="{$order_info[phone]}" />
      <input type='hidden' name="email" value="{$order_info[email]}" />
      <input type='hidden' name="ok_url" value="{$ok_url}" />
      <input type='hidden' name="nok_url" value="{$nok_url}" />
      <input type='hidden' name="callback_url" value="{$callback_url}" />
      <input type='hidden' name="discount[full]" value="{$discount}" />
      <input type='hidden' name="signature" value="{$signature}" />
EOT;


    // Products
    $it = 0;

    if ($order_info['shipping_cost']> 0){
      foreach ($order_info['shipping'] as $k => $v) {
      $price = $order_info['shipping_cost'];
      $product_name = $v['shipping'];
      $amount = $v['amount'];
      echo <<<EOT
      <input type="hidden" name="items[{$it}][description]" VALUE='{$product_name}'>
      <input type="hidden" name="items[{$it}][amount]" VALUE="{$price}">
      <input type="hidden" name="items[{$it}][quantity]" VALUE="1">
EOT;

    }
    $it++;
  }

  //payment subcharges
  if ( $order_info['payment_surcharge'] > 0 ){
    $price = $order_info['payment_surcharge'];
    $product_name = $order_info['payment_method']['surcharge_title'];
    $amount = 1;
    echo <<<EOT
  <input type="hidden" name="items[{$it}][description]" VALUE='{$product_name}'>
  <input type="hidden" name="items[{$it}][amount]" VALUE="{$price}">
  <input type="hidden" name="items[{$it}][quantity]" VALUE="{$amount}">
EOT;
    $it++;
  }
    if (!empty($order_info['products'])) {
    	foreach ($order_info['products'] as $k => $v) {
    		$price = fn_format_price($v['price'] - (fn_external_discounts($v) / $v['amount']));
    		$product_name = ($v['extra']['product']). " (".$v['amount'].")";
        $amount = $v['amount'];
    		if ($price <= 0) continue;
    		echo <<<EOT
      <input type="hidden" name="items[{$it}][description]" VALUE='{$product_name}'>
      <input type="hidden" name="items[{$it}][amount]" VALUE="{$price}">
      <input type="hidden" name="items[{$it}][quantity]" VALUE="{$amount}">
EOT;
    		$it++;
    	}
    }

    $msg = fn_get_lang_var('text_cc_processor_connection');
    $msg = str_replace('[processor]', 'pagamastarde', $msg);
    echo <<<EOT
    	</form>
    	<div align=center>{$msg}</div>
     </body>
    </html>
EOT;
exit;
}
?>
