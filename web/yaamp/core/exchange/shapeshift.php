<?php

// https://info.shapeshift.io/api

// https://shapeshift.io/getcoins/

function shapeshift_api_query($method, $params='')
{
	$uri = "https://shapeshift.io/{$method}/{$params}";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$execResult = strip_tags(curl_exec($ch));
	$obj = json_decode($execResult, true);

	return $obj;
}
