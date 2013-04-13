<?php
/********************************************
 ./manual-update-cc.php?for={year-month}&force=(html|text|json)
 ./manual-update-cc.php?for={year-month}&action=clear

********************************************/


set_time_limit(0);
$starttime = microtime();

$GeoIPCountryWhoisJSON = 'http://'.$_SERVER["HTTP_HOST"].'/'.dirname($_SERVER["PHP_SELF"]).'/GeoIPCountryWhois.php?IP=%IP%';

require_once(dirname(dirname(__FILE__)).'/Hermes.settings.php');
require_once(dirname(__FILE__).'/settings.php');


function force_export($str, $exportid=''){
	global $minimize;
	if(($exportid!=='' && isset($_GET['export']) && $_GET['export'] == $exportid) || $exportid===TRUE){
		if(isset($_GET['force'])){switch($_GET['force']){
			case 'html': header("Content-type: text/html;"); break;
			case 'text': header("Content-type: text/plain;"); break;
			case 'json': default: header("Content-type: application/json;");
		}} else { header("Content-type: application/json;"); }
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		$str = str_replace(array('\n','\t'), array("\n","\t"), $str);
		if($minimize===TRUE){$str = str_replace(array("\n","\t",': ',', ','  '), array('','',':',',',' '), $str);}
		if(isset($_GET['force']) && $_GET['force']=='html'){
			preg_match_all('#"task": ([^,]+),#i', $str, $r); $pv = end($r[1]);
			preg_match('#"counter": ([^,]+),#i', $str, $r); $pmax = (isset($r[1]) ? $r[1] : '0');
			if(preg_match('#"status": "(complete|up-to-date)"#i', $str)){ $pv = $pmax; }
			$globals['progress-bar'] = '<progress value="'.$pv.'" max="'.$pmax.'">'.$pv.' of '.$pmax.' done</progress>';
			$str = html_template_replace($str, 'progress-bar', $globals['progress-bar']);
			//print $globals['progress-bar'];
		}
		print $str;
		exit;
	}
}
function hermes_manual_cc_update($month=NULL, $domain="http://localhost/"){
	global $starttime;
	if($month==NULL || !preg_match("/^(20[0-9]{2}\-[01][0-9]|blacklist|whitelist|authorlist)$/i", $month)){ $month = date('Y-m'); }

	$dbfile = HERMES_SCROLL_LOCATION.$month.HERMES_SCROLL_EXTENSION;

	if(!file_exists($dbfile)){ return '{"domain": "'.$domain.'", "error": 404, "db": "'.$month.HERMES_SCROLL_EXTENSION.'", "when": "'.date('c').'"}'; }

	$dbraw = file_get_contents($dbfile);

	if(isset($_GET['action']) && in_array($_GET['action'], array('clear','clear-all'))){
		$dbraw = str_replace(', "REMOTE_ADDR_CC": ""', '', $dbraw);

		$dbraw = preg_replace("#(\"REMOTE_ADDR_CC\": \"[^\"]{0,}\")(, \"REMOTE_ADDR_CC\": \"[^\"]{0,}\")#i", "\\1", $dbraw);

		if($_GET['action'] == 'clear-all'){ $dbraw = preg_replace("#(, )?(\"REMOTE_ADDR_CC\": \"[^\"]{0,}\")#i", "", $dbraw); }

		file_put_contents($dbfile, $dbraw);
		return '{"domain": "'.$domain.'", "db": "'.basename($dbfile).'", "action": "db.clear(REMOTE_ADDR_CC)", "when": "'.date("c").'", "status": "cleared"}';
	}

	$count = preg_match_all("#(\"REMOTE_ADDR\": \"([0-9\.]+)\"([^\n\}]{0,}))#i", $dbraw, $set);
	//*debug*/ print_r($set);

	$result = array(); $c = 0; $cacheIP = array(); $replace_task = array();
	foreach($set[2] as $i=>$IP){
		$match = preg_match("#\"REMOTE_ADDR_CC\": \"([^\"]{0,})\"#i", $set[3][$i], $subset);
		if(!in_array($IP, $cacheIP)){
			if(!$match){
				$CC = str_replace('&infin;', '', get_CC_by_JSON($IP));
				$result[$i] = '{"task": '.$i.', "IP": "'.$IP.'", "cc": "'.$CC.'"}';
				#/*do instantaniously */ $dbraw = str_replace('"REMOTE_ADDR": "'.$IP.'"', '"REMOTE_ADDR": "'.$IP.'", "REMOTE_ADDR_CC": "'.$CC.'"', $dbraw);
				/*do delayed*/ $replace_task['"REMOTE_ADDR": "'.$IP.'"'] = '"REMOTE_ADDR": "'.$IP.'", "REMOTE_ADDR_CC": "'.$CC.'"';
				$c++; if($c >= 40 || (double) test_time($starttime) > 4.00 /*sec*/){ $result[$i] = '{"action": "break"}'; $status = 'break'; $refresh = '<script>location.reload(true)</script>'; $break = TRUE; break; }
			}
		}
//		else{
//			if($match){ $CC = $subset[1]; } else { $CC = ''; }
//			$result[$i.'b'] = '{"task": '.$i.', "IP": "'.$IP.'", "cc": "'.$CC.'", "status": "already found"}';
//		}
		$cacheIP[] = $IP;
	}
	if(!isset($break) || $break != TRUE){ $status = ($month == date('Y-m') ? 'up-to-date' : 'complete'); $result[] = '{"action": "'.$status.'"}'; }

	#do break:
	if(count($replace_task) >= 1){
		/*Latency fix*/ $dbraw = file_get_contents($dbfile);
		foreach($replace_task as $from=>$to){ $dbraw = str_replace($from, $to, $dbraw); }
		file_put_contents($dbfile, $dbraw);
	}

	#/*do instantaniously */ file_put_contents($dbfile, $dbraw);

	if($count == 0 && is_array($result) && count($result)>0){ $count = (count($result)-1); }
	return (isset($_GET['force']) && $_GET['force'] == 'html'
			? '<html>'.html_template((isset($_GET['for']) ? $_GET['for'] : NULL), (isset($_GET['force']) ? $_GET['force'] : NULL)).'<pre>'
			: NULL)
			.'{"domain": "'.$domain.'", "db": "'.basename($dbfile).'", "action": "db.add(REMOTE_ADDR_CC)", "when": "'.date("c").'", "counter": '.$count.', "result": ['."\n\t".implode(",\n\t", $result)."\n".'], "processtime": "'.test_time($starttime).'", "status": "'.$status.'"}'
			.(isset($_GET['force']) && $_GET['force'] == 'html' ? '</pre>'.(isset($refresh) ? $refresh : NULL).'</html>' : NULL);
}
function get_CC_by_JSON($IP=NULL){
	global $GeoIPCountryWhoisJSON;
	if(preg_match("#^[0-9]{1,3}[\.][0-9]{1,3}[\.][0-9]{1,3}[\.][0-9]{1,3}$#", $IP)){
		$c = explode('.', $IP); $bool = (count($c)==4 ? TRUE : FALSE);
		for($i=0;$i<4;$i++){$c[$i] = (integer) $c[$i]; if(!($c[$i]>=0 && $c[$i]<=255)){$bool = FALSE;}}
		if($bool){
			$uri = str_replace('%IP%', $IP, $GeoIPCountryWhoisJSON);
			$json = file_get_contents($uri);
			if(preg_match("#\"cc\": \"([^\"]+)\"#i", $json, $set)){
				return $set[1];
			}
		}
	}
	return FALSE;
}
function html_template($for=NULL, $force=NULL){
	$str = file_get_contents(dirname(__FILE__).'/manual-update-cc.template.html');
	if(preg_match('#^([0-9]{4})[-]([0-9]{2})$#i', $for, $set)){
		list($trash, $for_y, $for_m) = $set;
		$str = html_template_replace($str, 'for-m', $for_m, 'SELECTED="1"', NULL);
		$str = html_template_replace($str, 'for-y', $for_y, 'SELECTED="1"', NULL);
		$str = html_template_replace($str, 'force', $force, 'SELECTED="1"', NULL);
	}
	return $str;
}
function html_template_replace($str, $anker, $value=NULL, $match_str=NULL, $nonmatch_str=NULL){
	preg_match_all('#%select:'.$anker.'\[([^\]]+)\]%#i', $str, $set);
	foreach($set[1] as $i=>$v){
		if(is_array($value)){
			if(in_array($v, $value)){ $str = str_replace('%select:'.$anker.'['.$v.']%', $match_str, $str); }
			else{ $str = str_replace('%select:'.$anker.'['.$v.']%', $nonmatch_str, $str); }
		}
		else{
			if($v == $value){ $str = str_replace('%select:'.$anker.'['.$v.']%', $match_str, $str); }
			else{ $str = str_replace('%select:'.$anker.'['.$v.']%', $nonmatch_str, $str); }
		}
	}
	if(!is_array($value)){ $str = str_replace('%'.$anker.'%', $value, $str); }
	return $str;
}
function test_time($start=TRUE){
	global $TEST_TIME;
	$current = microtime();
	if(!$TEST_TIME){$TEST_TIME = $current;}
	$my = ($start===TRUE ? $TEST_TIME : $start);
	$a = explode(' ', $my);
		$x = $a[0]+$a[1];
	$b = explode(' ', $current);
		$y = $b[0]+$b[1];
	$result = $y-$x;
	if($start===TRUE){
		$TEST_TIME = $current;
	}
	return $result;
}
force_export(hermes_manual_cc_update((isset($_GET["for"]) ? $_GET["for"] : NULL), $domain), TRUE);
?>