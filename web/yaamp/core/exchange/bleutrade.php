<?php

// close to bittrex api

function bleutrade_api_query($method, $params='')
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_BLEUTRADE_SECRET')) define('EXCH_BLEUTRADE_SECRET', '');

	// optional secret key
	if (empty(EXCH_BLEUTRADE_SECRET) && strpos($method, 'public') === FALSE) return false;
	if (empty(EXCH_BLEUTRADE_KEY) && strpos($method, 'public') === FALSE) return false;

	$apikey = EXCH_BLEUTRADE_KEY; // your API-key
	$apisecret = EXCH_BLEUTRADE_SECRET; // your Secret-key

	$nonce = time();
	//$mt = explode(' ', microtime());
	//$nonce = $mt[1].substr($mt[0], 2, 6);

	$uri = "https://bleutrade.com/api/v2/$method?apikey=$apikey&nonce=$nonce$params";

	$sign = hash_hmac('sha512', $uri, $apisecret);
	$ch = curl_init($uri);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("apisign:$sign"));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSLVERSION, 1 /*CURL_SSLVERSION_TLSv1*/);
	curl_setopt($ch, CURLOPT_SSL_SESSIONID_CACHE, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Bleutrade API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	curl_setopt($ch, CURLOPT_ENCODING , '');

	$data = curl_exec($ch);
	$obj = json_decode($data);

	if(!is_object($obj)) {
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		debuglog("bleutrade: $method failed ($status) ".strip_data($data).' '.curl_error($ch));
	}

	curl_close($ch);

	return $obj;
}
