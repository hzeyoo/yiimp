<?php

// markets https://stocks.exchange/api2/markets
// prices https://stocks.exchange/api2/ticker
// prices https://stocks.exchange/api2/prices (unused)

function stocksexchange_api_query($method)
{
	$uri = "https://stocks.exchange/api2/$method";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);

	$res = curl_exec($ch);
	$obj = json_decode($res);

	return $obj;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////

// manual update of one market
function stocksexchange_update_market($market)
{
	$exchange = 'stocksexchange';
	if (is_string($market))
	{
		$symbol = $market;
		$coin = getdbosql('db_coins', "symbol=:sym", array(':sym'=>$symbol));
		if(!$coin) return false;
		$pair = strtoupper($symbol).'_BTC';
		$market = getdbosql('db_markets', "coinid={$coin->id} AND name='$exchange'");
		if(!$market) return false;

	} else if (is_object($market)) {

		$coin = getdbo('db_coins', $market->coinid);
		if(!$coin) return false;
		$symbol = $coin->getOfficialSymbol();
		$pair = strtoupper($symbol).'_BTC';
		if (!empty($market->base_coin)) $pair = strtoupper($symbol).'_'.$market->base_coin;
	}

	$t1 = microtime(true);
	$tickers = stocksexchange_api_query('ticker');
	if(empty($tickers) || !is_array($tickers)) return false;
	foreach ($tickers as $t) {
		if ($t->market_name == $pair) $ticker = $t;
	}
	if (!isset($ticker)) return false;

	$price2 = ($ticker->bid+$ticker->ask)/2;
	$market->price = AverageIncrement($market->price, $ticker->bid);
	$market->price2 = AverageIncrement($market->price2, $price2);
	$market->pricetime = time();
	$market->save();

	$apims = round((microtime(true) - $t1)*1000,3);
	user()->setFlash('message', "$exchange $symbol price updated in $apims ms");

	return true;
}
