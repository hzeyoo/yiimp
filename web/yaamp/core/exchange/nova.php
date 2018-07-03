<?php

// markets

function nova_api_query($method, $params='')
{
	$uri = "https://novaexchange.com/remote/$method/";
	if (!empty($params))
		$uri .= "$params";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$res= curl_exec($ch);
	$obj = json_decode($res);

	return $obj;
}

function nova_api_user($method, $params='')
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_NOVA_SECRET')) define('EXCH_NOVA_SECRET', '');

	if (empty(EXCH_NOVA_KEY) || empty(EXCH_NOVA_SECRET)) return false;

	$uri = "https://novaexchange.com/remote/private/$method/$params";

	$headers = array(
		'Content-Type: application/x-www-form-urlencoded',
	);

	$mt = explode(' ', microtime());
	$nonce = $mt[1].substr($mt[0], 2, 6);

	$postdata = array(
		'apikey'=>EXCH_NOVA_KEY,
		'apisecret'=>EXCH_NOVA_SECRET,
		'nonce'=>$nonce,
	);
	$postdata = http_build_query($postdata, '', '&');

	$ch = curl_init($uri);
	//curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_SSLVERSION, 1 /*CURL_SSLVERSION_TLSv1*/);
	curl_setopt($ch, CURLOPT_SSL_SESSIONID_CACHE, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Nova API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	curl_setopt($ch, CURLOPT_ENCODING , '');

	$data = curl_exec($ch);
	$res = json_decode($data);

	if(!is_object($res) || $res->status != 'success') {
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		debuglog("nova: $method failed ($status) ".strip_data($data).' '.curl_error($ch));
	}

	curl_close($ch);

	return $res;
}
