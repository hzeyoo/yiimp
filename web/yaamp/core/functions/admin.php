<?php

// Functions commonly used in admin pages

function getAdminSideBarLinks()
{
$links = <<<end
<a href="/site/exchange">Exchanges</a>&nbsp;
<a href="/site/user">Users</a>&nbsp;
<a href="/site/worker">Workers</a>&nbsp;
<a href="/site/version">Version</a>&nbsp;
<a href="/site/earning">Earnings</a>&nbsp;
<a href="/site/payments">Payments</a>&nbsp;
<a href="/site/monsters">Big Miners</a>&nbsp;
end;
	return $links;
}

// shared by wallet "tabs", to move in another php file...
function getAdminWalletLinks($coin, $info=NULL, $src='wallet')
{
	$html = CHtml::link("<b>COIN PROPERTIES</b>", '/site/update?id='.$coin->id);
	if($info) {
		$html .= ' || '.$coin->createExplorerLink("<b>EXPLORER</b>");
		$html .= ' || '.CHtml::link("<b>PEERS</b>", '/site/peers?id='.$coin->id);
		$html .= ' || '.CHtml::link("<b>CONSOLE</b>", '/site/console?id='.$coin->id);
		$html .= ' || '.CHtml::link("<b>TRIGGER</b>", '/site/triggers?id='.$coin->id);
		if ($src != 'wallet')
			$html .= ' || '.CHtml::link("<b>{$coin->symbol}</b>", '/site/coin?id='.$coin->id);
	}

	if(!$info && $coin->enable)
		$html .= '<br/>'.CHtml::link("<b>STOP COIND</b>", '/site/stopcoin?id='.$coin->id);

	if($coin->auto_ready)
		$html .= '<br/>'.CHtml::link("<b>UNSET AUTO</b>", '/site/unsetauto?id='.$coin->id);
	else
		$html .= '<br/>'.CHtml::link("<b>SET AUTO</b>", '/site/setauto?id='.$coin->id);

	$html .= '<br/>';

	if(!empty($coin->link_bitcointalk))
		$html .= CHtml::link('forum', $coin->link_bitcointalk, array('target'=>'_blank')).' ';

	if(!empty($coin->link_github))
		$html .= CHtml::link('git', $coin->link_github, array('target'=>'_blank')).' ';

	if(!empty($coin->link_site))
		$html .= CHtml::link('site', $coin->link_site, array('target'=>'_blank')).' ';

	$html .= CHtml::link('google', 'http://google.com/search?q='.urlencode($coin->name.' '.$coin->symbol.' bitcointalk'), array('target'=>'_blank'));

	return $html;
}

/////////////////////////////////////////////////////////////////////////////////////////////
