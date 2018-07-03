<?php

function strip_data($data)
{
        $out = strip_tags($data);
        $out = preg_replace("#[\t ]+#", " ", $out);
        $out = preg_replace("# [\r\n]+#", "\n", $out);
        $out = preg_replace("#[\r\n]+#", "\n", $out);
        if (strpos($out, 'CloudFlare') !== false) $out = 'CloudFlare error';
        return $out;
}

require_once("poloniex.php");
require_once("bitstamp.php");
require_once("bittrex.php");
require_once("ccexapi.php");
require_once("bleutrade.php");
require_once("kraken.php");
require_once("yobit.php");
require_once("shapeshift.php");
require_once("bter.php");
require_once("empoex.php");
require_once("jubi.php");
require_once("alcurex.php");
require_once("cryptopia.php");
require_once("livecoin.php");
require_once("nova.php");

/* Format an exchange coin Url */
function getMarketUrl($coin, $marketName)
{
	$symbol = !empty($coin->symbol2) ? $coin->symbol2 : $coin->symbol;
	$lowsymbol = strtolower($symbol);
	$base = 'BTC';

	$market = trim($marketName);
	if (strpos($marketName, ' ')) {
		$parts = explode(' ',$marketName);
		$market = $parts[0];
		$base = $parts[1];
		if (empty($base)) {
			debuglog("warning: invalid market name '$marketName'");
			$base = dboscalar(
			"SELECT base_coin FROM markets WHERE coinid=:id AND name=:name", array(
				':id'=>$coin->id, ':name'=>$marketName,
			));
		}
	}

	$lowbase = strtolower($base);

	if($market == 'alcurex')
		$url = "https://alcurex.org/index.php/crypto/market?pair={$lowsymbol}_{$lowbase}";
	else if($market == 'bittrex')
		$url = "https://bittrex.com/Market/Index?MarketName={$base}-{$symbol}";
	else if($market == 'poloniex')
		$url = "https://poloniex.com/exchange#{$lowbase}_{$lowsymbol}";
	else if($market == 'bleutrade')
		$url = "https://bleutrade.com/exchange/{$symbol}/{$base}";
	else if($market == 'bter')
		$url = "https://bter.com/trade/{$lowsymbol}_{$lowbase}";
	else if($market == 'cryptopia')
		$url = "https://www.cryptopia.co.nz/Exchange?market={$symbol}_{$base}";
	else if($market == 'c-cex')
		$url = "https://c-cex.com/?p={$lowsymbol}-{$lowbase}";
	else if($market == 'empoex')
		$url = "http://www.empoex.com/trade/{$symbol}-{$base}";
	else if($market == 'jubi')
		$url = "http://jubi.com/coin/{$lowsymbol}";
	else if($market == 'livecoin')
		$url = "https://www.livecoin.net/trade/?currencyPair={$symbol}%2F{$base}";
	else if($market == 'nova')
		$url = "https://novaexchange.com/market/{$base}_{$symbol}/";
	else if($market == 'yobit')
		$url = "https://yobit.net/en/trade/{$symbol}/{$base}";
	else
		$url = "";

	return $url;
}
