<?php

function doNovaTrading($quick=false)
{
	$exchange = 'nova';
	$updatebalances = true;

	if (exchange_get($exchange, 'disabled')) return;

	$balances = nova_api_user('getbalances');
	if(!is_object($balances) || $balances->status != 'success' || !isset($balances->balances)) return;

	//{"currencyid":1,"currency":"BTC","amount":"0.00000000","amount_trades":"0.00000000","amount_total":"0.00000000","currencyname":"Bitcoin","amount_lockbox":"0.00000000"}

	$savebalance = getdbosql('db_balances', "name='{$exchange}'");
	if (is_object($savebalance)) {
		$savebalance->balance = 0;
		$savebalance->save();

		dborun("UPDATE markets SET balance=0 WHERE name='{$exchange}'");
	}

	foreach($balances->balances as $balance)
	{
		if ($balance->currency == 'BTC') {
			if (is_object($savebalance)) {
				$savebalance->balance = $balance->amount;
				$savebalance->save();
			}
			continue;
		}

		if ($updatebalances) {
			// store available balance in market table
			$coins = getdbolist('db_coins', "symbol=:symbol OR symbol2=:symbol",
				array(':symbol'=>$balance->currency)
			);
			if (empty($coins)) continue;
			foreach ($coins as $coin) {
				$market = getdbosql('db_markets', "coinid=:coinid AND name='{$exchange}'", array(':coinid'=>$coin->id));
				if (!$market) continue;
				$market->balance = $balance->amount;
				$market->ontrade = $balance->amount_trades;
				$market->balancetime = time();
				$market->save();
			}
		}
	}

	if (!YAAMP_ALLOW_EXCHANGE) return;

	$flushall = rand(0, 8) == 0;
	if($quick) $flushall = false;
}
