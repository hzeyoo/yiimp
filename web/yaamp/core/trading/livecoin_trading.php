<?php

function doLivecoinTrading($quick=false)
{
	$exchange = 'livecoin';
	$updatebalances = true;

	if (exchange_get($exchange, 'disabled')) return;

	$flushall = rand(0, 8) == 0;
	if($quick) $flushall = false;

	// https://www.livecoin.net/api/userdata#paymentbalances
	$balances = livecoin_api_user('payment/balances');

	if(!$balances || !is_array($balances)) return;

	$savebalance = getdbosql('db_balances', "name='$exchange'");
	if (is_object($savebalance)) {
		$savebalance->balance = 0;
		$savebalance->save();
	}

	foreach($balances as $balance)
	{
		if($balance->currency == 'BTC' && $balance->type == "available") {
			if (!is_object($savebalance)) continue;
			$savebalance->balance = $balance->value;
			$savebalance->save();
			continue;
		}

		if ($updatebalances) {
			// store available balance in market table
			$coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
				array(':symbol'=>$balance->currency)
			);
			if (empty($coins)) continue;
			foreach ($coins as $coin) {
				$market = getdbosql('db_markets', "coinid=:coinid AND name='$exchange'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				if ($balance->type == "available")
					$market->balance = $balance->value;
				elseif ($balance->type == "trade")
					$market->ontrade = $balance->value;
				$market->balancetime = time();
				$market->save();
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;
}
