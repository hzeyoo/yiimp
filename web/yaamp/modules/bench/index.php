<?php

if (empty($algo)) $algo = 'all';

$sqlFilter='1';
if ($vid) $sqlFilter = 'B.vendorid LIKE '.sqlQuote($vid);

// --------------
$algos = array();
$in_db = dbolist("SELECT algo, count(id) as count FROM benchmarks B WHERE $sqlFilter GROUP BY algo ORDER BY algo ASC, count DESC");
foreach ($in_db as $row) {
	$algos[$row['algo']] = $row['count'];
}
$options = '<option value="all">Show all</option>';
foreach($algos as $a => $count) {
	if($a == $algo)
		$options .= '<option value="'.$a.'" selected="selected">'.$a.'</option>';
	else
		$options .= '<option value="'.$a.'">'.$a.'</option>';
}
// --------------
$algoFilter = $algo != 'all' ? ' AND B.algo='.sqlQuote($algo) : '';

$chips = array();
$in_db = dbolist("SELECT DISTINCT B.idchip as id, B.type, C.chip as name FROM benchmarks B".
	" LEFT JOIN bench_chips C ON C.id=B.idchip WHERE B.idchip IS NOT NULL $algoFilter AND $sqlFilter GROUP BY B.idchip ORDER BY type DESC, name ASC");
foreach ($in_db as $row) {
	$chips[$row['id']] = $row['name'];
}
$optchips = '<option value="0">Show all</option>';
foreach($chips as $id => $name) {
	if($id == $idchip)
		$optchips .= '<option value="'.$id.'" selected="selected">'.$name.'</option>';
	else
		$optchips .= '<option value="'.$id.'">'.$name.'</option>';
}

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

include('functions.php');

$algo = user()->getState('bench-algo');
if (empty($algo)) $algo = 'all';
if (empty($idchip)) $idchip = NULL;
if (empty($vid)) $vid = NULL;

$this->pageTitle = "Benchmarks";

$bench = new db_benchmarks;
if($algo != 'all') $bench->algo = $algo;
$bench->vendorid = $vid;
$bench->idchip = $idchip;
$dp = $bench->search();
$db_rows = $dp->getData();

echo <<<end

<div align="right" style="margin-bottom: 2px; margin-right: 0px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>

<style type="text/css">
tr.ssrow.filtered { display: none; }
.page .footer { width: auto; };
</style>

<div align="right" style="margin-top: -22px; margin-right: 140px;">
Select Algo: <select class="filter" id="algo_select">{$options}</select>&nbsp;
Chip: <select class="filter" id="chip_select">{$optchips}</select>&nbsp;
</div>

end;

echo '<p style="margin-top: -20px; margin-bottom: 4px; line-height: 22px; font-weight: bolder;">';
if ($algo == 'all') {
	echo "Last 50 results";
} else  {
	echo "Last 50 $algo results";
	echo ", ".CHtml::link(" show totals", '/bench/algo?algo='.$algo);
}
echo '</p>';

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	widgets: ['zebra','filter'],
	textExtraction: {
		1: function(node, table, n) { return $(node).attr('data'); },
		5: function(node, table, n) { return $(node).attr('data'); },
		6: function(node, table, n) { return $(node).attr('data'); }
	},
	widgetOptions: {
		filter_external: '.search',
		filter_columnFilters: false,
		filter_childRows : true,
		filter_ignoreCase: true
	}
}");

$actions = '';
if ($this->admin) {
	$actions = '<th width="30" data-sorter="">Admin</th>';
}

echo <<<END
<thead>
<tr>
<th class="algo" data-sorter="text">Algo</th>
<th data-sorter="text">Time</th>
<th data-sorter="text">Chip</th>
<th data-sorter="text">Device</th>
<th data-sorter="text">Arch</th>
<th data-sorter="text">Vendor ID</th>
<th data-sorter="numeric">Hashrate</th>
<th data-sorter="numeric" title="Intensity (-i) for GPU or Threads (-t) for CPU miners">Int.</th>
<th data-sorter="numeric" title="MHz">Freq</th>
<th data-sorter="numeric" title="Watts if available">W</th>
<th data-sorter="currency" title="mBTC/day">Cost</th>
<th data-sorter="text">Client</th>
<th data-sorter="text">OS</th>
<th data-sorter="text">Driver / Compiler</th>
{$actions}
</tr>
</thead><tbody>
END;

foreach ($db_rows as $row) {

	if (!isset($row['algo'])) continue;
	if ($row['chip'] == 'Virtual') continue;

	echo '<tr class="ssrow">';

	$hashrate = Itoa2(1000*round($row['khps'],3),3).'H';
	$age = datetoa2($row['time']);

	echo '<td class="algo">'.CHtml::link($row['algo'],'/bench?algo='.$row['algo']).'</td>';
	echo '<td data="'.$row['time'].'">'.$age.'</td>';
	echo '<td>'.($row['idchip'] ? CHtml::link($row['chip'],'/bench?chip='.$row['idchip']) : $row['chip']).'</td>';
	if ($row['type'] == 'cpu') {
		echo '<td>'.formatCPU($row).'</td>';
		echo '<td>'.$row['arch'].'</td>';
		echo '<td>'.CHtml::link($row['vendorid'],'/bench?vid='.$row['vendorid']).'</td>';
	} else {
		echo '<td>'.$row['device'].getProductIdSuffix($row).'</td>';
		echo '<td>'.formatCudaArch($row['arch']).'</td>';
		echo '<td>'.CHtml::link($row['vendorid'],'/bench?vid='.$row['vendorid']).'</td>';
	}

	echo '<td data="'.$row['khps'].'">'.$hashrate.'</td>';

	if ($row['type'] == 'cpu') // threads
		echo '<td data="'.$row['throughput'].'">'.$row['throughput'].'</td>';
	else if ($algo == 'neoscrypt')
		echo '<td data="'.$row['throughput'].'" title="neoscrypt intensity is different">'.$row['throughput'].'*</td>';
	else
		echo '<td data="'.$row['throughput'].'" title="'.$row['throughput'].' threads">'.$row['intensity'].'</td>';

	echo '<td>'.($row['freq'] ? $row['freq'] : '-').'</td>';
	echo '<td>'.(empty($row['power']) ? '-' : $row['power']).'</td>';
	echo '<td>'.(empty($row['power']) ? '-' : mbitcoinvaluetoa(powercost_mBTC($row['power']))).'</td>';
	echo '<td>'.formatClientName($row['client']).'</td>';
	echo '<td>'.$row['os'].'</td>';
	echo '<td>'.$row['driver'].'</td>';

	if ($this->admin) {
		$props = array('style'=>'color: darkred;');
		echo '<td>'.CHtml::link("delete", '/bench/del?id='.$row['id'], $props).'</td>';
	}

	echo '</tr>';
}

echo '</tbody>';

if (!empty($algo)) {

	if ($idchip) $sqlFilter .= ' AND idchip='.intval($idchip);

	$avg = dborow("SELECT AVG(khps) as khps, AVG(power) as power, AVG(intensity) as intensity, AVG(freq) as freq, ".
		"COUNT(*) as records ".
		"FROM benchmarks B WHERE algo=:algo AND power > 5 AND $sqlFilter", array(':algo'=>$algo)
	);

	if (arraySafeVal($avg, 'records') == 0) {
		$avg = dborow("SELECT AVG(khps) as khps, '' as power, '' as intensity, '' as freq, ".
			"COUNT(*) as records ".
			"FROM benchmarks B WHERE algo=:algo AND $sqlFilter", array(':algo'=>$algo)
		);
	}

	if (arraySafeVal($avg, 'records') > 0) {
		echo '<tfoot><tr class="ssfoot">';

		echo '<th class="algo">'.CHtml::link($algo,'/bench?algo='.$algo).'</td>';
		echo '<th>&nbsp;</td>';

		echo '<th colspan="4">Average ('.$avg["records"].' records)</td>';

		echo '<th>'.Itoa2(1000*round($avg['khps'],3),3).'H</td>';
		echo '<th>'.($avg['intensity'] ? round($avg['intensity'],1) : '').'</td>';
		echo '<th>'.($avg['freq'] ? round($avg['freq']) : '').'</td>';
		echo '<th>'.($avg['power'] ? round($avg['power']) : '').'</td>';
		echo '<th>'.($avg['power'] ? mbitcoinvaluetoa(powercost_mBTC($avg['power'])) : '').'</td>';

		$hpw = 0;
		if (floatval($avg['power']) > 0) {
			$hpw = floatval($avg['khps']) / floatval($avg['power']);
		}
		echo '<th>'.($hpw ? Itoa2(1000*round($hpw,3),3).'H/W' : '').'</td>';

		echo '<th colspan="3">&nbsp;</td>';

		echo '</tr></tfoot>';
	}
}

echo'</table><br/>';

echo <<<end

<p style="margin: 0; padding: 0 4px;">
<a href="/bench/devices">Show current devices in the database</a><br/>
<a href="/site/benchmarks">Learn how to submit your results</a><br/>
<br/>
</p>

<script type="text/javascript">

$('select.filter').change(function(event) {
	algo = jQuery('#algo_select').val();
	chip = jQuery('#chip_select').val();
	window.location.href = '/bench?algo='+algo+'&chip='+chip;
});

</script>

end;
