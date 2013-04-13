<?php
$starttime = microtime();
$IP = (isset($_GET['IP']) ? $_GET['IP'] : $_SERVER['REMOTE_ADDR']);

$error[0] = '{mode: "error", text: "IP not in range"}';
$error[1] = '{mode: "error", text: "misformed IP"}';
$error[2] = '{mode: "error", text: "GeoIPCountryWhois.csv not found"}';

function is_ip($str, $output=FALSE){
	$bool = TRUE;
	if(preg_match("#^[0-9]{1,3}[\.][0-9]{1,3}[\.][0-9]{1,3}[\.][0-9]{1,3}$#i", (string) $str)){
		$ip = explode(".", $str); $r = array();
		for($i=0;$i<4;$i++){
			$ip[$i] = (int) $ip[$i];
			if($ip[$i] >= 0 && $ip[$i] <= 255){ $r[$i] = (string) $ip[$i]; /*fix*/ if(!$r[$i]){$r[$i] = '0';} }
			else{ $bool = FALSE; }
		}
		$output = ($output===FALSE ? FALSE : ($output===TRUE ? implode('.', $r) : $r));
	} else { $bool = FALSE; }
	return ($output===FALSE ? $bool : $output);
}
function ip2ipdec($IP){
	$r = is_ip($IP, array());
	$IPdec = ( $r[0]*(255*255*255) + $r[1]*(255*255) + $r[2]*(255) + $r[3] );
	return ($IPdec);
}
function ipbetween($between, $larger, $smaller){
		#/*debug*/ print $smaller." + ".$between." + ".$larger." <br/>\n";
	if(is_ip($between) && is_ip($larger) && is_ip($smaller) && iplarger($larger, $smaller)){
		$bool = (iplarger($between, $smaller) && iplarger($larger, $between));
		#/*debug*/ print $smaller." < ".$between." < ".$larger." = ".($bool ? 'true' : 'false')." <br/>\n";
		return $bool;
	}
	return FALSE;
}
function iplarger($larger,$smaller){
	if(is_ip($larger) && is_ip($smaller)){
		$a = explode('.', $larger);
		$b = explode('.', $smaller);
		for($i=0;$i<4;$i++){
			if((int) $a[$i] < (int) $b[$i]){ return FALSE; }
			elseif((int) $a[$i] > (int) $b[$i]){ return TRUE; }
			/* else ($a[$i] == $b[$i]) */
		}
		return TRUE;
	}
	return FALSE;
}
function json_output($str){
	#header("Content-type: application/javascript+json;");
	#header("Content-type: text/plain;");
	print $str;
	exit;
}
function csv_search($IPreal, $raw, $depth=4, $needle=NULL){
	$empty = array('cc'=>'&infin;','country'=>'UNKNOWN','IP-start'=>0,'IP-finish'=>0);
	if($needle==NULL){$needle = $IPreal;}
	$ip = explode('.', $needle);
	$real = NULL;
	for($i=0;$i<$depth;$i++){
		if($real !== NULL){ $real .= '.'; }
		$real .= $ip[$i];
	}
	if($depth!=4){$real .= '.';}
	if($depth==0){$real = str_replace('*', '[0-9]', $needle);}

	$IPdec = ip2ipdec($needle);

	$s = array();
	$pattern = "#(^|\n)(\"".str_replace('.', '[\.]', $real)."[^\n]+)(\n|$)#i";
	$count = preg_match_all($pattern, $raw, $set);
	//*debug*/ global $starttime; print '<!-- '.test_time($starttime).' ('.$count.') '.str_replace("\n",'\n',$pattern).' -->'."\n";
 	if( is_array($set[2]) && $count != 0){
		foreach($set[2] as $i=>$v){
			$s[$i] = csv_read($v, array('IP-start','IP-finish','IPdec-start','IPdec-finish','cc','country') );
			if(ipbetween($IPreal, $s[$i]['IP-finish'], $s[$i]['IP-start'])){
				return $s[$i];
			}
		}
	}
	else{ return ($depth > 0 ? csv_search($IPreal, $raw, $depth-1, $needle) : (substr($needle, 0,1)!='*' ? csv_search($IPreal, $raw, 0, preg_replace("#([0-9])([\*\.]+)$#", "*\\2", preg_replace("#^([0-9\*]+[\.])(.*)$#", "\\1", $needle)) ) : array_merge($empty, array('country'=>'OUT OF RANGE')) )); }
	//*debug*/ $set[2]['pattern'] = $pattern; $set[2]['count'] = $count; $set[2]['IPreal'] = $IPreal; $set[2]['real'] = $real; $set[2]['depth'] = $depth; return $set[2];
	//*last*/ return end($s);
	return $empty;
}
function csv_read($str=NULL, $keys=array()){
	if(substr($str, 0, 1) == '"' && substr($str, -1) == '"'){
		$str = substr($str, 1, -1);
		$records = explode('","', $str);

		$items = array();
		foreach($records as $i=>$v){
			if(isset($keys[$i])){ $items[$keys[$i]] = $v; }
			else{ $items[$i] = $v; }
		}
		return $items;
	}
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

#/*test*/ $t = array(0=>'127.0.0.1',1=>'126.255.0.128',3=>'127.0.0.1',2=>'192.168.10.102'); for($i=0;$i<4;$i++){ print $t[$i] ." > ". $t[(($i+1) == 4 ? 0 : $i+1)] ." = ". (string) iplarger($t[$i], $t[(($i+1) == 4 ? 0 : $i+1)])." / ". (string) iplarger($t[(($i+1) == 4 ? 0 : $i+1)], $t[$i])."<br/>\n"; } print $t[1].">".$t[0].">".$t[2]." = ".(string) ipbetween($t[0], $t[1], $t[2])."/".(string) ipbetween($t[0], $t[2], $t[1])."<br/>\n";

if(basename($_SERVER["SCRIPT_NAME"]) == basename(__FILE__)){
	if($r = is_ip($IP, array())){
		if(function_exists('geoip_record_by_name') && function_exists('geoip_db_filename')){
			$result = @geoip_record_by_name($IP);
			
			json_output('{"IP": "'.$IP.'", "continent": "'.$result['continent_code'].'", "cc": "'.$result['country_code'].'", "country": "'.$result['country_name'].'", "city": "'.$result['city'].'", "latitude": "'.$result['latitude'].'", "longitude": "'.$result['longitude'].'"'.(isset($rstr)&&strlen($rstr)>1 ? $rstr : NULL).', "processtime": "'.test_time($starttime).'s", "fingerprint": "'.md5_file(geoip_db_filename(GEOIP_COUNTRY_EDITION)).'", "filetype": "'.strtoupper(pathinfo(geoip_db_filename(GEOIP_COUNTRY_EDITION), PATHINFO_EXTENSION)).'", "filesize": '.filesize(geoip_db_filename(GEOIP_COUNTRY_EDITION)).'}');
		}
		elseif(file_exists(dirname(__FILE__).'/GeoIPCountryWhois.csv')){ #Manual CVS lookup
			$IP = implode('.', $r);
	
			$raw = file_get_contents(dirname(__FILE__).'/GeoIPCountryWhois.csv');
	
			$result = csv_search($IP, $raw, 2);
			//*debug*/ $rstr= NULL; if(isset($result['pattern'])){ foreach($result as $i=>$v){if(in_array($v, array('pattern','count','IPreal','real','depth') )){$rstr .= ', "'.$i.'": "'.$v.'"';}}} $rstr .= ', "$result": "'.str_replace('"','&quot;',print_r($result, TRUE)).'"';
			json_output('{"IP": "'.$IP.'", "IP-range": "'.$result['IP-start'].' - '.$result['IP-finish'].'", "cc": "'.$result['cc'].'", "country": "'.$result['country'].'"'.(isset($rstr)&&strlen($rstr)>1 ? $rstr : NULL).', "processtime": "'.test_time($starttime).'s", "fingerprint": "'.md5($raw).'", "filetype": "'.strtoupper('cvs').'", "filesize": '.filesize(dirname(__FILE__).'/GeoIPCountryWhois.csv').'}');
		}
		else { json_output($error[2]); }
	
	} else { json_output($error[1]); }
}
?>