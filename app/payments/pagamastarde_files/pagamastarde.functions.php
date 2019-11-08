<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * @param        $secret_key
 * @param        $fields
 * @param string $mode
 *
 * @return string
 */
function get_signature($secret_key, $fields, $mode = 'sha512')
{
    $string = $secret_key;
    foreach ($fields as $key => $value) {
        $string.=$value;
    }

    if ($mode == 'sha512') {
        return hash('sha512', $string);
    }

    return sha1($string);
}

/**
 * @param $order_info
 * @param $processor_data
 *
 * @return array
 */
function get_orders_fields($order_info, $processor_data)
{
    $fields = array();
    $order_id = $fields['order_id'];
    $fields['account_id'] = $processor_data['processor_params']['pagamastarde_public_key'];
    $fields['order_id'] = $order_id;
    $fields['amount'] = $order_info['total']*100;
    $fields['description'] = 'Order #'.$fields['order_id'];
    $fields['currency'] = $order_info['secondary_currency'];
    $fields['full_name'] = $order_info[b_firstname].' '.$order_info[b_lastname];
    $fields['address[street]'] = $order_info[b_address];
    $fields['address[city]'] = $order_info[b_city];
    $fields['address[province]'] = $order_info[b_state_descr];
    $fields['address[zipcode]'] = $order_info[b_zipcode];
    $fields['shipping[street]'] = $order_info[s_address];
    $fields['shipping[city]'] = $order_info[s_city];
    $fields['shipping[province]'] = $order_info[s_state_descr];
    $fields['shipping[zipcode]'] = $order_info[s_zipcode];
    $fields['phone'] = $order_info[phone];
    $fields['email'] = $order_info[email];
    $fields['ok_url'] = fn_url(
        "payment_notification.notify?payment=pagamastarde&order_id=$order_id&transmode=success",
        AREA,
        'http'
    );
    $fields['nok_url'] = fn_url('checkout.checkout');
    $fields['callback_url'] = fn_url(
        "payment_notification.callback?payment=pagamastarde&order_id=$order_id&transmode=success",
        AREA,
        'http'
    );
    $fields['purchase_country'] = CART_LANGUAGE;
    $signature_fields = array(
        $fields['account_id'],
        $fields['order_id'],
        $fields['amount'],
        $fields['currency'],
        $fields['ok_url'],
        $fields['nok_url'],
        $fields['callback_url']
    );
    $fields['signature'] = get_signature(
        $processor_data['processor_params']['pagamastarde_secret_key'],
        $signature_fields
    );

    $it = 0;
    //PAYMENT SUBCHARGES
    if ($order_info['payment_surcharge']>0) {
        $fields["items[$it][description]"] = $order_info['payment_method']['surcharge_title'];
        $fields["items[$it][amount]"] = 1;
        $fields["items[$it][quantity]"] = $order_info['payment_surcharge'];
        $it++;
    }

    //SHIPPING
    if ($order_info['shipping_cost']> 0) {
        foreach ($order_info['shipping'] as $k => $v) {
            $fields["items[$it][description]"] = $v['shipping'];
            $fields["items[$it][amount]"] = $order_info['shipping_cost'];
            $fields["items[$it][quantity]"] = 1;
            $it++;
        }
    }

    //PRODUCTS
    if (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $k => $v) {
            $price = fn_format_price($v['price'] - (fn_external_discounts($v) / $v['amount']));
            if ($price <= 0) {
                continue;
            }

            $fields["items[$it][description]"] = ($v['extra']['product']). " (".$v['amount'].")";
            $fields["items[$it][amount]"] = $price;
            $fields["items[$it][quantity]"] = $v['amount'];
            $it++;
        }
    }

    return $fields;
}

/**
 * @param null $order_id
 *
 * @return bool
 */
function check_notification($order_id = null)
{
    $i = 0;
    $found = false;
    do {
        $i++;
        sleep(1);
        //Status => P=PROCESADO, C=COMPLETADO, I=CANCELADO, N=INCOMPLETA, O=ABIERTO, F=FRUSTRADO
        $valid_id = db_get_field("SELECT order_id FROM ?:orders WHERE order_id = ?i AND status = 'N'", $order_id);
        if (empty($valid_id)) {
            $found = true;
        }
    } while ($i<5 && !$found && $order_id!=null);

    return $found;
}

/**
 * @param null $temp
 *
 * @return array
 */
function callback($temp = null)
{
    $response = array('code'=>500,'message'=>'KO');

    try {
        if (!count($temp)) {
            $response['message'] = 'Empty body';
            return $response;
        }

        $data = $temp['data'];
        $order_id = $data['order_id'];
        $payment_id = db_get_field("SELECT payment_id FROM ?:orders WHERE order_id = ?i", $order_id);
        $processor_data = fn_get_payment_method_data($payment_id);

        if (!count($processor_data)) {
            $response['message'] = 'No quote found';
            $response['code'] = 429;
            return $response;
        }

        $signature_fields = array();
        $signature_fields['account_id'] = $temp['account_id'];
        $signature_fields['api_version'] = $temp['api_version'];
        $signature_fields['event'] = $temp['event'];
        $signature_fields['data_id'] = $temp['data']['id'];

        $signature_check = get_signature(
            $processor_data['processor_params']['pagamastarde_secret_key'],
            $signature_fields,
            'sha1'
        );
        $signature_check_sha512 = get_signature(
            $processor_data['processor_params']['pagamastarde_secret_key'],
            $signature_fields
        );

        if ($signature_check != $temp['signature'] && $signature_check_sha512 != $temp['signature']) {
            $response['code'] = 403;
            $response['message'] = 'Hack detected';
            return $response;
        }

        $pp_response = array();
        switch ($temp['event']) {
            case 'charge.created':
                $pp_response['order_status'] = 'P';
                $pp_response["reason_text"] = "Order ID:$order_id -- Paga+Tarde ID".$data['id'];
                $pp_response["transaction_id"] = $order_id;
                break;
            case 'charge.failed':
                $pp_response['order_status'] = 'F';
                $pp_response["reason_text"] = 'Callback received. Payment Failed. Order ID : ' . $order_id;
                $pp_response["transaction_id"] = $order_id;
                break;
        }

        fn_finish_payment($order_id, $pp_response);

        $response['code'] = 200;
        $response['message'] = 'OK';
    } catch (Exception $e) {
        $response['code'] = 500;
        $response['message'] = $e->getMessage();
    }

    return $response;
}
