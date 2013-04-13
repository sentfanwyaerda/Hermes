<?php
$file = dirname(__FILE__).DIRECTORY_SEPARATOR.'GeoIPCountryWhois.csv';
$dbraw = file($file); //file_get_contents

function neat($int, $decimal=0, $postfix=NULL){
	$setting = FALSE;
	if($setting==TRUE){ return '"'.number_format($int, $decimal, ',', ' ').$postfix.'"'; }
	else{ return $int; }
}

//*debug*/ print $file.' ('.strlen($dbraw).' | '.filesize($file).' : '.number_format((strlen($dbraw) / filesize($file)) * 100, 2).'%)'."\n";

$IP4MAX = (255*255*255*255);
if(!isset($_GET['force'])){$_GET['force'] = FALSE;} else { $_GET['force'] = strtolower($_GET['force']); }
header("Content-Type: ".(strtolower($_GET['force'])=="html" ? 'text/html' : 'application/json'));

if($_GET['force']=="html") /*debug*/ print '<pre>';
print '{"IP4:range": "0.0.0.0 - 255.255.255.255", "IP4:size": '.neat($IP4MAX).', '."\n".'"db:file": "GeoIPCountryWhois.csv", "db:version": "'.date('c', filemtime($file)).'", "db:src": "http://www.maxmind.com/app/geolite", "db:size": '.neat(filesize($file)).', '."\n".'"entries": '.count($dbraw); //.'}'."\n";

$db = array(); /* $db[$cc] = array('CN'=>"Unknown", 'CC'=>"XX", 'records'=>(int), 'size'=>(int)); */
foreach($dbraw as $i=>$line){
	$c = preg_match('#^"([^"]+)","([^"]+)","([^"]+)","([^"]+)","([^"]+)","([^"]+)"\s*$#i', $line, $entry);
	list($trash, $startIP, $endIP, $startNUM, $endNUM, $CC, $country) = $entry;
	if(!isset($db[$CC]) || !is_array($db[$CC])){
		$db[$CC] = array('CN'=>$country,'CC'=>$CC,'records'=>0,'size'=>0,'share'=>NULL);
	}
	$db[$CC]['records']++;
	$db[$CC]['size'] += ($endNUM - $startNUM);
}
$allocatedIP4 = 0;
foreach($db as $CC=>$r){
	$db[$CC]['share'] = number_format((($db[$CC]['size']/$IP4MAX)*(100)), 6, ',', ' ').'%';
	$allocatedIP4 += $db[$CC]['size'];
}
print ', "countries": '.count($db).', '."\n".'"CC:allocated": '.neat($allocatedIP4).', "CC:known": "'.number_format(($allocatedIP4/$IP4MAX)*100, 3).'%"'; //.'}'."\n";
//*debug*/  print_r($db); print '</pre>';

$db['_'] = array('CN'=>"",'CC'=>"_",'records'=>0,'size'=>($IP4MAX-$allocatedIP4),'share'=>number_format(100-(($allocatedIP4/$IP4MAX)*100), 3).'%');

$CClist = array_keys($db); sort($CClist);
print ', '."\n".'"db": ['."\n"; foreach($CClist as $i=>$CC){ print "\t".'{"CC": "'.$CC.'", "records": '.$db[$CC]['records'].', "size": '.$db[$CC]['size'].', "share": "'.$db[$CC]['share'].'", "CN": "'.$db[$CC]['CN'].'"}'."\n"; } print ']';


print '}';
if($_GET['force']=="html") /*debug*/ print '</pre>';
?>