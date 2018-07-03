<?php

// https://api.livecoin.net/exchange/ticker

function livecoin_api_query($method, $params='')
{

	$uri = "https://api.livecoin.net/$method";
	if (!empty($params))
		$uri .= "/$params";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult);

	return $obj;
}

// payment/balance

function livecoin_api_user($method, $params=NULL)
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_LIVECOIN_SECRET')) define('EXCH_LIVECOIN_SECRET', '');
	if (empty(EXCH_LIVECOIN_KEY) || empty(EXCH_LIVECOIN_SECRET)) return false;
	$apikey = EXCH_LIVECOIN_KEY;

	$exchange = "livecoin";

	if (empty($params)) $params = array();
	ksort($params);
	$fields = http_build_query($params, '', '&');
	$signature = strtoupper(hash_hmac('sha256', $fields, EXCH_LIVECOIN_SECRET));

	$headers = array(
		'Content-Type: application/json; charset=utf-8',
		'Api-key: '.$apikey,
		'Sign: '.$signature,
	);

	$url = "https://api.livecoin.net/$method?$fields";

	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; YiiMP API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	curl_setopt($ch, CURLOPT_ENCODING , '');

	$res = curl_exec($ch);
	if($res === false)
	{
		$e = curl_error($ch);
		debuglog("$exchange: $e");
		curl_close($ch);
		return false;
	}

	$result = json_decode($res);
	if(!is_object($result) && !is_array($result)) {
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		debuglog("$exchange: $method failed ($status) $res");
	}

	curl_close($ch);

	return $result;
}

// untested yet

function livecoin_api_post($method, $params=NULL)
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_LIVECOIN_SECRET')) define('EXCH_LIVECOIN_SECRET', '');
	if (empty(EXCH_LIVECOIN_KEY) || empty(EXCH_LIVECOIN_SECRET)) return false;
	$apikey = EXCH_LIVECOIN_KEY;

	$exchange = "livecoin";

	$url = "https://api.livecoin.net/$method";

	if (empty($params)) $params = array();
	ksort($params);
	$postFields = http_build_query($params, '', '&');
	$signature = strtoupper(hash_hmac('sha256', $postFields, EXCH_LIVECOIN_SECRET));

	$headers = array(
		'Content-Type: application/json; charset=utf-8',
		'Api-key: '.$apikey,
		'Sign: '.$signature,
	);

	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
	curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; YiiMP API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	curl_setopt($ch, CURLOPT_ENCODING , '');

	$res = curl_exec($ch);
	if($res === false)
	{
		$e = curl_error($ch);
		debuglog("$exchange: $e");
		curl_close($ch);
		return false;
	}

	$result = json_decode($res);
	if(!is_object($result) && !is_array($result)) {
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		debuglog("$exchange: $method failed ($status) $res");
	}

	curl_close($ch);

	return $result;
}
