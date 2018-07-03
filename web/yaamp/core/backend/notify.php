<?php

function NotifyCheckRules()
{
	$time = time();
	$since = $time - (10 * 60); // for later cmdline check, cron doesn't need that

	$search = new db_notifications;
	$notifications = $search->with('coin')->findAll(
		"enabled AND lastchecked <= {$since} ".
		" AND (nc.installed OR nc.enable OR nc.watch)"
	);

	foreach($notifications as $rule)
	{
		$coin = $rule->coin;

		$condw = explode(" ", $rule->conditiontype);
		$field = $condw[0];
		if (count($condw) < 2) {
			debuglog("notify: invalid conditiontype for {coin->symbol}, need at least one space like 'price >'");
			continue;
		}
		$comp = $condw[1];

		$triggered = false;
		if (array_key_exists($field, $rule->coin->attributes)) {
			$value  = $rule->coin->attributes[$field];
			$valref = $rule->conditionvalue;
			switch ($comp) {
			case '<':
				$triggered = $value < $valref;
				break;
			case '>':
				$triggered = $value > $valref;
				break;
			case '<=':
				$triggered = $value <= $valref;
				break;
			case '>=':
				$triggered = $value >= $valref;
				break;
			case '=':
			case '==':
				$triggered = $value == $valref;
				break;
			}
		} else {
			debuglog("notify: invalid field '{$field}' in conditiontype for {$coin->symbol}!");
			continue;
		}

		if ($triggered && $rule->lasttriggered == $rule->lastchecked) {
			// already notified
			$rule->lasttriggered = $time;
			$rule->lastchecked = $time;
			$rule->save();
			continue;
		} else {
			$rule->lasttriggered = $triggered ? $time : 0;
			$rule->lastchecked = $time;
			$rule->save();
		}

		if (!$triggered) continue;

		$value = bitcoinvaluetoa($value);
		debuglog("trigger: {$coin->symbol} {$rule->conditiontype} {$rule->conditionvalue} ({$value})");

		switch ($rule->notifytype)
		{
			case 'email':
				$subject = "[{$coin->symbol}] Trigger {$rule->conditiontype} {$rule->conditionvalue} ({$value})";

				$message  = "Description: {$rule->description}\n\n";

				$message .= "Field: {$field}\n";
				$message .= "Value: {$value} at ".strftime("%Y-%m-%d %T %z", $time)."\n";

				$dest = YAAMP_ADMIN_EMAIL;
				if (!empty($rule->notifycmd) && strstr($notifycmd, "@")) {
					$dest = $rule->notifycmd;
				}

				$res = mail($dest, $subject, $message);
				if (!$res)
					debuglog("notify: unable to send mail to {$dest}!");
				break;

			case 'system':

				$command = $rule->notifycmd;

				// replace some possible vars in user command
				$command = str_replace('$X', $value, $command);
				$command = str_replace('$F', $field, $command);
				$command = str_replace('$T', $conditiontype, $command);
				$command = str_replace('$V', $conditionvalue, $command);
				$command = str_replace('$N', $coin->name, $command);
				$command = str_replace('$SYM', $coin->symbol, $command);
				$command = str_replace('$S2', $coin->symbol2, $command);

				$res = system($command);
				if ($res === false)
					debuglog("notify: unable to execute {$rule->notifycmd}!");
				break;
		}
	}
}
