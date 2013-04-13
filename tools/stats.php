<?php
require_once(dirname(dirname(__FILE__)).'/Hermes.settings.php');

set_time_limit(0);
$starttime = microtime();
function shortlink_counter_stats($month=NULL, $domain="http://localhost/", $recache=FALSE){
	global $author_ipset, $robot_ipset, $minimize;
	if($month==NULL || !preg_match("/^20[0-9]{2}\-[01][0-9]$/i", $month)){ $month = date('Y-m'); }

	$dbfile = HERMES_SCROLL_LOCATION.$month.HERMES_SCROLL_EXTENSION;
	$cachefile = str_replace(HERMES_SCROLL_EXTENSION, HERMES_SCROLL_EXTENSION.'.cache', $dbfile);

	if($recache == FALSE && file_exists($cachefile) && $month != date("Y-m") && !isset($_GET["export"])){
		$cache = file_get_contents($cachefile);
		return $cache;
	}	


	if(!file_exists($dbfile)){ return '{"domain": "'.$domain.'", "error": 404, "db": "'.$month.HERMES_SCROLL_EXTENSION.'", "when": "'.date('c').'"}'; }

	$dbraw = file_get_contents($dbfile);

	preg_match_all('/"when": "([^"]+)"/i', $dbraw, $uset);
	$stats['hits'] = count($uset[0]);

	preg_match_all('/"action": "shortlink"/i', $dbraw, $uset);
	$stats['shortlink-hits'] = count($uset[0]);

	$idset = array();
	preg_match_all('/"identity": "([^"]+)"/i', $dbraw, $uset);
	foreach($uset[1] as $v){$idset[$v] = $v;}
	$stats['unique'] = count($idset);

	preg_match_all('/"robot": "true"/i', $dbraw, $uset);
	$stats['robot-hits'] = count($uset[0]);

	$robot_idset = array(); if(!isset($robot_ipset)){ $robot_ipset = array(); }
	#preg_match_all('/"identity": "([^"]+)", "robot": "([^"]+)"/i', $dbraw, $uset);
	#foreach($uset[1] as $v){$robot_idset[$v] = $v;}
	$stats['robot-unique'] = count($robot_idset);

	$author_idset = array(); if(!isset($author_ipset)){ $author_ipset = array(); }
	preg_match_all('/"identity": "([^"]+)",[^}]+"query": "[^"]{0,}preview=true[^"]{0,}"/', $dbraw, $uset);
	foreach($uset[1] as $v){$author_idset[$v] = $v;}

	/*#*/ $stats['real-hits'] = ($stats['hits']-$stats['robot-hits']);
	/*#*/ $stats['real-unique'] = ($stats['unique']-$stats['robot-unique']);

	$stats['author-hits'] = '-1';

	$urls = array();
	preg_match_all('#(http://[a-z0-9./_-]+)#i', $dbraw, $urlset); # ?=&
	foreach($urlset[1] as $v){if(!isset($urls[$v])){ $urls[$v] = 0; } $urls[$v] += 1;}

	$query = array();
	preg_match_all('/"query": "([^"]+)"/i', $dbraw, $uset);
	foreach($uset[1] as $v){if(!isset($query[$v])){ $query[$v] = 0; } $query[$v] += 1;}

	$langset = array();
	preg_match_all('/"lang": "([^"]+)"/i', $dbraw, $uset);
	foreach($uset[1] as $v){if(!isset($langset[$v])){ $langset[$v] = 0; } $langset[substr($v, 0, 2)] += 1;}

	$items = array();
	preg_match_all('/"item": "([^"]+)"/i', $dbraw, $uset);
	foreach($uset[1] as $v){if(!isset($items[$v])){ $items[$v] = 0; } $items[$v] += 1;}

	$agents = $languages = $ipsets = $realqueries = array();
	#Dovetail Crawler
	foreach(explode("\n", $dbraw) as $i=>$record){
		preg_match('/"when": "([^"]+)"/i', $record, $uset);
		$when = (isset($uset[1]) ? ISO8601toDate($uset[1]) : NULL );
		preg_match('/"identity": "([^"]+)"/i', $record, $uset);
		$identity = (isset($uset[1]) ? $uset[1] : NULL);

		/*##DOVETAIL 0##*/
		if(preg_match('/"HTTP_USER_AGENT": "([^"]+)"/i', $record, $uset)){
			if(!isset($http_user_agent[$uset[1]]['unique'])){ $http_user_agent[$uset[1]]['unique'] = 0; } $http_user_agent[$uset[1]]['unique'] += 1; $http_user_agent[$uset[1]]['identities'][] = $identity; $currhua = $uset[1];
			if(preg_match('/(crawler|spider|google|bot|facebookexternalhit|yahoo|URL Resolver|UnwindFetchor|urllib|metauri|webagent|[\+]?http[:][\/]{2}|ips-agent|Babya Discoverer|Postrank|TwitterFeed|funwebproducts|NetcraftSurveyAgent|TalkTalk|Virus|marks|httpclient|deepnet|ICS|PycURL|[\:]{2}|[@]|webster)/i', $uset[1])){$robot_idset[] = $identity; }
			preg_match_all('#((.NET |WINDOWS |MAC OS |iPhone|MEDIA CENTER |CPU )?[^/ \[\(;,:]+[/ ][0-9._]+|windows|linux|ipad|ios|apple|firefox|mozilla|safari|applewebkit|gecko|msie)#i', $uset[1], $uuset);
			foreach($uuset[1] as $v){if(!isset($agents[$v])){ $agents[$v] = 0; } $agents[$v] += 1; }
		} else { $currhua = ''; }

		/*##DOVETAIL PREPARE 1##*/
		if(!isset($thour[date('H', $when)]['hits'])){$thour[date('H', $when)]['hits'] = 0;}
		if(!isset($thour[date('H', $when)]['shortlink-hits'])){$thour[date('H', $when)]['shortlink-hits'] = 0;}
		if(!isset($thour[date('H', $when)]['author-hits'])){$thour[date('H', $when)]['author-hits'] = 0;}
		if(!isset($thour[date('H', $when)]['robot-hits'])){$thour[date('H', $when)]['robot-hits'] = 0;}
		if(!isset($thour[date('H', $when)]['real-hits'])){$thour[date('H', $when)]['real-hits'] = 0;}
		if(!isset($thour[date('H', $when)]['unique'])){$thour[date('H', $when)]['unique'] = 0;}
		if(!isset($thour[date('H', $when)]['shortlink-unique'])){$thour[date('H', $when)]['shortlink-unique'] = 0;}
		if(!isset($thour[date('H', $when)]['robot-unique'])){$thour[date('H', $when)]['robot-unique'] = 0;}
		if(!isset($thour[date('H', $when)]['real-unique'])){$thour[date('H', $when)]['real-unique'] = 0;}

		/*##DOVETAIL PREPARE 2##*/
		if(!isset($tdaymonth[date('Y-m-d', $when)]['hits'])){$tdaymonth[date('Y-m-d', $when)]['hits'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['shortlink-hits'])){$tdaymonth[date('Y-m-d', $when)]['shortlink-hits'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['author-hits'])){$tdaymonth[date('Y-m-d', $when)]['author-hits'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['robot-hits'])){$tdaymonth[date('Y-m-d', $when)]['robot-hits'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['real-hits'])){$tdaymonth[date('Y-m-d', $when)]['real-hits'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['unique'])){$tdaymonth[date('Y-m-d', $when)]['unique'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['shortlink-unique'])){$tdaymonth[date('Y-m-d', $when)]['shortlink-unique'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['robot-unique'])){$tdaymonth[date('Y-m-d', $when)]['robot-unique'] = 0;}
		if(!isset($tdaymonth[date('Y-m-d', $when)]['real-unique'])){$tdaymonth[date('Y-m-d', $when)]['real-unique'] = 0;}

		/*##DOVETAIL 1+2##*/
		$thour[date('H', $when)]['hits'] += 1; $tdaymonth[date('Y-m-d', $when)]['hits'] += 1;
		if(preg_match('/"action": "shortlink"/i', $record)){ $thour[date('H', $when)]['shortlink-hits'] += 1; $tdaymonth[date('Y-m-d', $when)]['shortlink-hits'] += 1;}

		if(preg_match('/"REMOTE_ADDR": "([^"]+)"/i', $record, $uset)){
			if(in_array($uset[1], $author_ipset)){ $author_idset[] = $identity; }
			elseif(in_array($identity, $author_idset) && !in_array($uset[1], $author_ipset)){ $author_ipset[] = $uset[1]; }
			if(in_array($uset[1], $robot_ipset)){ $robot_idset[] = $identity; }
			elseif(in_array($identity, $robot_idset) && !in_array($uset[1], $robot_ipset)){ $robot_ipset[] = $uset[1]; }

			$thour[date('H', $when)]['unique'] += 1; $tdaymonth[date('Y-m-d', $when)]['unique'] += 1;
			if(preg_match('/"action": "shortlink"/i', $record)){ $thour[date('H', $when)]['shortlink-unique'] += 1; $tdaymonth[date('Y-m-d', $when)]['shortlink-unique'] += 1;}
			if(in_array($identity, $robot_idset)){ $thour[date('H', $when)]['robot-unique'] += 1; $tdaymonth[date('Y-m-d', $when)]['robot-unique'] += 1; }
			else{ $thour[date('H', $when)]['real-unique'] += 1; $tdaymonth[date('Y-m-d', $when)]['real-unique'] += 1; }
		}
		if(in_array($identity, $author_idset)){ $thour[date('H', $when)]['author-hits'] += 1; $tdaymonth[date('Y-m-d', $when)]['author-hits'] += 1; }
		if(in_array($identity, $robot_idset)){ $thour[date('H', $when)]['robot-hits'] += 1; $tdaymonth[date('Y-m-d', $when)]['robot-hits'] += 1; }
		else{ $thour[date('H', $when)]['real-hits'] += 1; $tdaymonth[date('Y-m-d', $when)]['real-hits'] += 1; }

		/*##DOVETAIL 3##*/
		if(preg_match('/"query": "([^"]+)"/i', $record, $uset)){
			preg_match_all('/((feed|category_name|lang)=[^&\/%+]+|(p|page_id|m|cat)=[0-9]+)/i', $uset[1], $uuset);
			foreach($uuset[1] as $v){
				/*fix*/$v = str_replace('index.php', '', $v);
				if(!isset($realqueries[$v]) || !is_array($realqueries[$v])){ $realqueries[$v] = array('hits' => 0); }
				$realqueries[$v]['hits'] += 1;
				if(!isset($realqueries[$v]['shortlink-hits'])){$realqueries[$v]['shortlink-hits'] = 0;}
				if(!isset($realqueries[$v]['author-hits'])){$realqueries[$v]['author-hits'] = 0;}
				if(!isset($realqueries[$v]['robot-hits'])){$realqueries[$v]['robot-hits'] = 0;}
				if(!isset($realqueries[$v]['real-hits'])){$realqueries[$v]['real-hits'] = 0;}

				if(preg_match('/"action": "shortlink"/i', $record)){ $realqueries[$v]['shortlink-hits'] += 1;}
				if(in_array($identity, $author_idset)){ $realqueries[$v]['author-hits'] += 1; }
				if(in_array($identity, $robot_idset)){ $realqueries[$v]['robot-hits'] += 1; }
				else{ $realqueries[$v]['real-hits'] += 1; }
			}
		}

		/*##DOVETAIL 4+##*/

		if(preg_match('/"HTTP_ACCEPT_LANGUAGE": "([^"]+)"/i', $record, $uset)){if(!isset($languages[$uset[1]]['unique'])){ $languages[$uset[1]]['unique'] = 0; } $languages[$uset[1]]['unique'] += 1; $languages[$uset[1]]['identities'][] = $identity; }
		if(preg_match('/"REMOTE_ADDR": "([^"]+)"/i', $record, $uset)){ if(!isset($ipsets[$uset[1]]['unique'])){ $ipsets[$uset[1]]['unique'] = 0; } $ipsets[$uset[1]]['unique'] += 1; $ipsets[$uset[1]]['identities'][] = $identity; $http_user_agent[$currhua]['REMOTE_ADDR'][] = $uset[1]; }
		if(preg_match('/"REMOTE_ADDR_CC": "([^"]{0,})"/i', $record, $uset)){
			if(!isset($ipccsets[$uset[1]]['unique'])){ $ipccsets[$uset[1]]['unique'] = 0; } $ipccsets[$uset[1]]['unique'] += 1;
			if(!isset($ipccsets[$uset[1]]['shortlink-unique'])){$ipccsets[$uset[1]]['shortlink-unique'] = 0;}
			if(!isset($ipccsets[$uset[1]]['author-unique'])){$ipccsets[$uset[1]]['author-unique'] = 0;}
			if(!isset($ipccsets[$uset[1]]['robot-unique'])){$ipccsets[$uset[1]]['robot-unique'] = 0;}
			if(!isset($ipccsets[$uset[1]]['real-unique'])){$ipccsets[$uset[1]]['real-unique'] = 0;}

			$ipccsets[$uset[1]]['identities'][] = $identity;
			if(preg_match('/"action": "shortlink"/i', $record)){ $ipccsets[$uset[1]]['shortlink-unique'] += 1;}
			if(in_array($identity, $author_idset)){ $ipccsets[$uset[1]]['author-unique'] += 1; }
			if(in_array($identity, $robot_idset)){ $ipccsets[$uset[1]]['robot-unique'] += 1; }
			else{ $ipccsets[$uset[1]]['real-unique'] += 1; }
		}

	}
	/*fix*/ unset($tdaymonth['1970-01-01']);

	/*##DOVETAIL RETOUCH##*/

	$stats['author-hits'] = find_hits_per_identity($dbraw, $author_idset);
	$stats['author-unique'] = count($author_idset);
	$stats['author-IPs'] = count($author_ipset);

	$stats['robot-hits'] = find_hits_per_identity($dbraw, $robot_idset);
	$stats['robot-unique'] = count($robot_idset);
	$stats['robot-IPs'] = count($robot_ipset);

	$stats['real-hits'] = ($stats['hits']-$stats['robot-hits']);
	$stats['real-unique'] = ($stats['unique']-$stats['robot-unique']);

	$stp = NULL;
	$stp .= '[';
	$stp .= '["real-hits", '.@round($stats['real-hits']/$stats['real-unique'],2).', "real-unique"],';
	$stp .= '['.@round($stats['hits']/$stats['real-hits'],2).', '.@round($stats['hits']/$stats['real-unique'],2).', '.@round($stats['unique']/$stats['real-unique'],2).'],';
	$stp .= '["hits", '.@round($stats['hits']/$stats['unique'],2).', "unique"],';
	$stp .= '['.@round($stats['hits']/$stats['robot-hits'],2).', '.@round($stats['hits']/$stats['robot-unique'],2).', '.@round($stats['unique']/$stats['robot-unique'],2).'],';
	$stp .= '["robot-hits", '.@round($stats['robot-hits']/$stats['robot-unique'],2).', "robot-unique"]';
	$stp .= ']';

	if(!isset($ipccsets) || !is_array($ipccsets)){$ipccsets = array(); } foreach($ipccsets as $cc=>$ccset){
		if(is_array($ipccsets[$cc]['identities'])){
			$ipccsets[$cc]['hits'] = find_hits_per_identity($dbraw, $ccset['identities']);
			$ipccsets[$cc]['author-hits'] = find_hits_per_identity($dbraw, array_intersect($ccset['identities'], $author_idset));
			$ipccsets[$cc]['robot-hits'] = find_hits_per_identity($dbraw, array_intersect($ccset['identities'], $robot_idset));
			$ipccsets[$cc]['real-hits'] = ($ipccsets[$cc]['hits'] - $ipccsets[$cc]['robot-hits']);
		}
	}
	foreach($http_user_agent as $agent=>$ccset){
		if(isset($http_user_agent[$agent]['identities']) && is_array($http_user_agent[$agent]['identities'])){
			#$http_user_agent[$agent]['author-unique'] = count(array_intersect($ccset['identities'], $author_idset));
			#$http_user_agent[$agent]['robot-unique'] = count(array_intersect($ccset['identities'], $robot_idset));
			#$http_user_agent[$agent]['real-unique'] = ($http_user_agent[$agent]['unique'] - $http_user_agent[$agent]['robot-unique']);
			$http_user_agent[$agent]['hits'] = find_hits_per_identity($dbraw, $ccset['identities']);
			$http_user_agent[$agent]['author-hits'] = find_hits_per_identity($dbraw, array_intersect($ccset['identities'], $author_idset));
			$http_user_agent[$agent]['robot-hits'] = find_hits_per_identity($dbraw, array_intersect($ccset['identities'], $robot_idset));
			$http_user_agent[$agent]['real-hits'] = ($http_user_agent[$agent]['hits'] - $http_user_agent[$agent]['robot-hits']);
		}
	}

	/*##GLOBAL DOVETAIL REWRITE##*/
	foreach($stats as $key=>$value){
		$jsonar[$key] = '"'.$key.'": '.$value;
	} $json = implode(', ', $jsonar);
	#/*debug*/ $idset = array('#'.count($idset));




	$cache  = '{"domain": "'.$domain.'", "db": "'.$month.'.json-array.txt", "db-size": '.filesize($dbfile).', "range": "'.$month.'-01/P1M", "when": "'.date('c').'",\n'.$json.', \n"matrix": '.$stp.',';
	$cache .= ' \n"tables": {';
		$cache .= '\n\t"activity_per_hour": '.singlearraytojson($thour, 'hour', 'hits', '\n\t\t', '', 'tables.activity_per_hour');
		$cache .= ',\n\t"activity_per_day": '.singlearraytojson($tdaymonth, 'date', 'hits', '\n\t\t', '', 'tables.activity_per_day');
		$cache .= ',\n\t"item": '.singlearraytojson($items, 'item', 'hits', '\n\t\t', '', 'tables.item');
		$cache .= ',\n\t"language_select": '.singlearraytojson($langset, 'l', 'hits', '\n\t\t', '', 'tables.language_select');
		$cache .= ',\n\t"page_requests": '.singlearraytojson($realqueries, 'query', 'hits', '\n\t\t', '', 'tables.page_requests');
	#	$cache .= ',\n\t"agents": '.singlearraytojson($agents, 'agent', 'unique', '\n\t\t', '', 'tables.agents');
		$cache .= ',\n\t"HTTP_ACCEPT_LANGUAGE": '.singlearraytojson($languages, 'l', 'unique', '\n\t\t', '', 'tables.HTTP_ACCEPT_LANGUAGE', TRUE);
		$cache .= ',\n\t"REMOTE_ADDR_CC": '.singlearraytojson($ipccsets, 'cc', 'unique', '\n\t\t', '', 'tables.REMOTE_ADDR_CC', TRUE);
	/**/	$cache .= ',\n\t"HTTP_USER_AGENT": '.singlearraytojson($http_user_agent, 'agent', 'unique', '\n\t\t', '', 'tables.HTTP_USER_AGENT', array('identities', 'REMOTE_ADDR'));
	/**/	$cache .= ',\n\t"urls": '.singlearraytojson($urls, 'url', 'references', '\n\t\t', '', 'tables.urls', TRUE);
		$cache .= '\n\t},';
	$cache .= ' \n"sequences": {';
		$cache .= '\n\t"activity_per_hour": '.singlearraytosequencejson($thour, 'hour', 'hits', '\n\t\t', 'activity per hour', 'sequences.activity_per_hour');
		$cache .= ',\n\t"activity_per_day": '.singlearraytosequencejson($tdaymonth, 'date', 'hits', '\n\t\t', 'activity per day', 'sequences.activity_per_day');
		$cache .= ',\n\t"language_select": '.singlearraytosequencejson($langset, 'l', 'hits', '\n\t\t', 'selected languages', 'sequences.language_select');
		$cache .= ',\n\t"page_requests": '.singlearraytosequencejson($realqueries, 'query', 'hits', '\n\t\t', 'requested pages', 'sequences.page_requests');
		$cache .= ',\n\t"REMOTE_ADDR_CC": '.singlearraytosequencejson($ipccsets, 'cc', 'unique', '\n\t\t', 'countries of origin', 'sequences.REMOTE_ADDR_CC', TRUE);
		$cache .= '\n\t}';
	global $starttime;
	$cache .= ',\n"processtime": "'.test_time($starttime).'s"';
	$cache .= '\n}';

	# ',\n\t"queries": '.singlearraytojson($query, 'query', 'hits', '\n\t\t').', \n"identities": ["'.implode('", "', $idset).'"],\n"urls": '.singlearraytojson($urls, 'href', 'count').',\n"REMOTE_ADDR": '.singlearraytojson($ipsets, 'ip', 'unique', '\n\t\t').''

	/*return*/ $cache = str_replace(array('\n','\t'), array("\n","\t"), $cache);
	if($minimize===TRUE){$cache = str_replace(array("\n","\t",': ',', ','  '), array('','',':',',',' '), $cache);}

	if(($recache !== FALSE || !file_exists($cachefile)) && $month!=date("Y-m") && !isset($_GET["export"])){
		file_put_contents($cachefile, $cache);
	}
	return $cache;
}
function find_hits_per_identity($dbraw, $identities=array(), $size_limit=1000){
	#preg_match_all('/"identity": "('.implode('|', $identities).')"/i', $dbraw, $uset);
	#return count($uset[0]);

	$set = array_chunk(array_unique($identities), $size_limit);
	$hits = 0;
	foreach($set as $i=>$subset){
		if(count($subset)>0){
			$query = '#"identity": "('.str_replace(array('||','$','^','+','-','#','~','@','_'), array('|','[\$]','[\^]','[\+]','[\-]','[\#]','[\~]','[\@]','[_]'), implode('|', $subset)).')"#i';
			#/*debug*/ print '<!-- '.$query.' -->'."\n";
			preg_match_all($query, $dbraw, $uset);
			$hits += count($uset[0]);
		}
	}
	return $hits;
}
function ISO8601toDate($iso){
	return strtotime($iso);
}
function singlearraytojson($ar=array(), $x_var='x', $y_var='y', $newline=' ', $title='', $exportid='', $exclude=array()){
	if($exclude === TRUE || !is_array($exclude)){ $exclude = array('identities'); }
	$str = '[';
	if(is_array($ar)){foreach($ar as $x=>$y){
		if(is_array($y)){
			$str .= '{"'.$x_var.'": "'.$x.'"';
			foreach($y as $yn=>$yv){
				if(!in_array($yn, $exclude)){
					if(is_array($yv)){  $str .= ', "'.$yn.'": ["'.implode('", "', $yv).'"]'; }
					else{ $str .= ', "'.$yn.'": '.(is_int($yv) ? $yv : '"'.$yv.'"'); }
				}
			}
			$str .= '},'.$newline;
		}
		else{
			$str .= '{"'.$x_var.'": "'.$x.'", "'.$y_var.'": '.(is_int($y) ? $y : '"'.$y.'"').'},'.$newline;
		}
	}}
	$str = substr($str, 0, (-1*(1+strlen($newline))) ); #remove last ', '
	$str .= ']';
	force_export($newline.$str, $exportid);
	return $str;
}
function singlearraytosequencejson($ar=array(), $x_var='x', $y_var='y', $newline=' ', $title='', $exportid='', $exclude=array()){
# {"graphset":[{
#	"title": {"text":"*"},
#	"scale-x":{"values":["*","*"]},
# 	"series":[
#		{"values":["*","*"],"text":"*"},
# 	]
# }] }
	if($exclude === TRUE || !is_array($exclude)){ $exclude = array('identities'); }
	$extraline = (substr($newline, -2, 1) == '\\' ? substr($newline, -2) : substr($newline, -1));

	$str = '{"graphset":[{'.$newline;
	if($title!=''){ $str .= '"title": {"text":"'.$title.'"},'.$newline;}

	$scalex /*single array*/ = $scaley /*double array*/ = array();
	if(!isset($ar) || !is_array($ar)){$ar = array(); } foreach($ar as $x=>$y){
		if(!is_array($y)){$y = array($y_var=>$y);}
		$scalex[] = $x;
		foreach($y as $yn=>$yv){
			if(!in_array($yn, $exclude)){
				$scaley[$yn][] = $yv;
			}
		}
	}

	$str .= '"scale-x": {"values": ["'.implode('", "', $scalex).'"]},'.$newline;
	$str .= '"series": ['.$newline;
	$c = count($scaley); $i = 0;
	foreach($scaley as $y=>$x){
		$str .= $extraline.'{"values": ['.(is_array($x) ? implode(', ', $x) : $x).'], "text": "'.$y.'"}'.(++$i!=$c ? ',' : NULL).$newline;
	}
	$str .= '] }] }';
	force_export($newline.$str, $exportid);
	return $str;
}
function force_export($str, $exportid=''){
	global $minimize;
	if(($exportid!=='' && isset($_GET['export']) && $_GET['export'] == $exportid) || $exportid===TRUE){
		header("Content-type: application/json;");
		header("Content-type: text/plain;");
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		$str = str_replace(array('\n','\t'), array("\n","\t"), $str);
		if($minimize===TRUE){$str = str_replace(array("\n","\t",': ',', ','  '), array('','',':',',',' '), $str);}
		print $str;
		exit;
	}
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

require_once(dirname(__FILE__).'/settings.php');
force_export(shortlink_counter_stats((isset($_GET["for"])?$_GET["for"]:NULL), $domain, ((isset($_GET["cache"]) && $_GET["cache"]=='clear')||(isset($_GET["action"]) && $_GET["action"]=='purge') ? TRUE : FALSE)), TRUE);
?>