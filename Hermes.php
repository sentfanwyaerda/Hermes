<?php
/****************** DO NOT REMOVE OR ALTER THIS HEADER ******************************
*                                                                                   *
* Product: Hermes                                                                   *
*    Hermes is a Webapplication Activity Log and Statistics method, written in PHP. *
*                                                                                   *
* Latest version to download:                                                       *
*    https://github.com/sentfanwyaerda/Hermes                                       *
*                                                                                   *
* Documentation:                                                                    *
*    http://sent.wyaerda.org/Hermes/                                                *
*    https://github.com/sentfanwyaerda/Hermes/blob/master/README.md                 *
*                                                                                   *
* Authors:                                                                          *
*    Sent fan Wy&aelig;rda (hermes@sent.wyaerda.org) [creator, main]                *
*                                                                                   *
* License: cc-by-nd                                                                 *
*    Creative Commons, Attribution-No Derivative Works 3.0 Unported                 *
*    http://creativecommons.org/licenses/by-nd/3.0/                                 *
*    http://creativecommons.org/licenses/by-nd/3.0/legalcode                        *
*                                                                                   *
****************** CHANGES IN THE CODE ARE AT OWN RISK *****************************/

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Hermes.settings.php');

function Hermes($record=array(), $value=NULL){
	if(!is_array($record)){ $record = array((string) $record => (string) $value); }

	global $__HERMES_RECORD;
	if(!isset($__HERMES_RECORD) || !(is_object($__HERMES_RECORD) && get_class($__HERMES_RECORD) =='Hermes' ) ){
		$__HERMES_RECORD = new Hermes();
	}
	$__HERMES_RECORD->build_record($record);
}
class Hermes{
	public function Version($f=FALSE){ return '0.3.2'; }
	public function Product_url($u=FALSE){ return ($u === TRUE ? "https://github.com/sentfanwyaerda/Hermes" : "http://sent.wyaerda.org/Hermes/?version=".self::Version(TRUE).'&license='.str_replace(' ', '+', self::License()) );}
	public function Product($full=FALSE){ return "Hermes".(!($full===FALSE) ? " ".self::version(TRUE) : NULL); }
	public function License($with_link=FALSE){ return ($with_link ? '<a href="'.self::License_url().'">' : NULL).'cc-by-nd 3.0'.($with_link ? '</a>' : NULL); }
	public function License_url(){ return 'http://creativecommons.org/licenses/by-nd/3.0/'; }
	public function Product_base(){ return dirname(__FILE__).DIRECTORY_SEPARATOR; }
	public function Product_file($full=FALSE){ return ($full ? self::Product_base() : NULL).basename(__FILE__); }
	
	
	private $_record = NULL;
	public function Hermes(){
		$this->_record = array(/**/);
	}
	public function Messenger($record=array()){
		#$this->_record = array_merge($record, $this->_record);
	}
	public /*bool*/ function deliver(){
		/*(re)writes log entry*/
		if(HERMES_ENCRYPTED_RECORD){ /*encrypt record before writing*/ }
		return FALSE;
	}
	public function build_record($items=array(), $level=7, $do_write=TRUE){
		$identity = self::getIdentity($items);
		$dbfile = self::getCurrentScrollFile();
		#/*debug*/ print '<!-- Hermes::build_record \$dbfile: '.print_r($dbfile, TRUE).' -->'."\n";
		
		if(!is_array($items)){$items = array('item'=>$items);}

		if(!($this->_record == NULL)){ #loads current record to values
			$input = json_decode(substr(trim($this->_record), 0, -1), TRUE);
		}
		else { 
			$input = array(
				'when' => date('c'),
				'identity' => $identity,
				);
		}
		$input = array_merge($input, $items);

		
		$flags = array();
		$l = str_repeat('0', 6).decbin($level);
		if($l{strlen($l)-1}==1) $flags = array_merge($flags, array('HTTP_REFERER'));
		if($l{strlen($l)-2}==1 || ($l{strlen($l)-5}=='0' && $l{strlen($l)-3}==1)){ $input['identity'] = $identity; }
		else{ unset($input['identity']); unset($input['robot']); }
		if($l{strlen($l)-3}==1){ if($l{strlen($l)-5}==1 || self::_identity_known($identity, $dbfile)=='0'){
			$flags = array_merge($flags, array('HTTP_USER_AGENT','REMOTE_ADDR','REMOTE_HOST','HTTP_ACCEPT_LANGUAGE'));
		}}
		if($l{strlen($l)-4}==1) $flags = array_merge($flags, array('PHP_SELF','QUERY_STRING'));
		if($l{strlen($l)-6}==1){
			$input['debug:level'] = $level.':'.$l;
			$input['debug:identity:known'] = self::_identity_known($identity, $dbfile);
		}
		foreach($flags as $key){
			if(isset($_SERVER[$key])){$input[$key] = $_SERVER[$key];}
			if($key == 'REMOTE_ADDR' && function_exists('geoip_country_code_by_name')){
				$input['REMOTE_ADDR_CC'] = /*@ hides notice-warning on local addresses*/ @geoip_country_code_by_name($_SERVER[$key]);
			}
		}

		$rec = array();
		$record = '{';
		foreach($input as $el=>$val){
			$rec[] = '"'.$el.'": "'.str_replace(array('\\','"'), array('\\\\','\"'), $val).'"';
		}
		$record .= implode(', ',$rec);
		$record .= "},\r\n";

		if($do_write==TRUE){
			if($this->_record == NULL){ #ADD RECORD
				if(!file_exists($dbfile)){ @touch($dbfile); }
				$fp = @fopen($dbfile, 'a');
				@fwrite($fp, $record);
				@fclose($fp);
			}
			else{ #UPDATE RECORD
				$fp = @fopen($dbfile, 'r+');
				$dbraw = @fread($fp, filesize($dbfile));
				$dbraw = str_replace($this->_record, $record, $dbraw);
				@fwrite($fp, $dbraw);
				@fclose($fp);
			}
		}
		$this->_record = $record;
	
		return $record;
	}
	private function _identity_known($id, $dbfile){
		if(!file_exists($dbfile)){ return FALSE; }
		if(strlen($id) < 4){ return FALSE; }
		$dbraw = file_get_contents($dbfile);
		$raw = (int) '0'; #$raw = preg_match('#"identity": "'.$id.'"#i', $dbraw);
		$clean = (int) preg_match('#"identity": "'.self::_preg_unbracked($id).'"#i', $dbraw);
		return ($raw == (int) '0' && $clean > (int) '0' ? $clean : $raw);
	}
	
	public function getIdentity( /*mixed*/ $a=FALSE, $b=FALSE, $c=FALSE, $d=FALSE){
		$a = (is_array($a) && isset($a['REMOTE_ADDR']) ? $a['REMOTE_ADDR'] : ($a === FALSE || is_array($a) ? $_SERVER['REMOTE_ADDR']: $a));
		$b = (is_array($a) && isset($a['REMOTE_HOST']) ? $a['REMOTE_HOST'] : ($b === FALSE && isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : $b));
		$c = (is_array($a) && isset($a['HTTP_USER_AGENT']) ? $a['HTTP_USER_AGENT'] : ($c === FALSE ? $_SERVER['HTTP_USER_AGENT']: $c));
		$d = (is_array($a) && isset($a['HTTP_ACCEPT_LANGUAGE']) ? $a['HTTP_ACCEPT_LANGUAGE'] : ($d === FALSE ? $_SERVER['HTTP_ACCEPT_LANGUAGE']: $d));		
		$hash = md5($c.$a.$b.$d);
		$identity = Xnode::large_base_convert($hash,16,HERMES_IDENTITY_BASE);
		/*"-fix*/ $identity = str_replace('"', HERMES_BASE_FIX_CHARACTER, $identity);
		//*debug*/ print '<!-- '.$c.$a.$b.$d.' = '.$hash.' -->';
		return $identity;
	}
	public function getScroll($current=TRUE, $multiple=FALSE){
		if($current == TRUE && $multiple == FALSE){ return self::getLatestScrollID(); }
		#$dbname = date(HERMES_SCROLL_FORMAT);
		#$list = self::listScrolls(str_replace(HERMES_SCROLL_FORMAT_DROP, '', str_replace('x', '0', $dbname)));
		#
		#if($multiple === FALSE){
		#	$dbname = str_replace(HERMES_SCROLL_FORMAT_DROP, '', $dbname);
		#	$dbfile = HERMES_SCROLL_LOCATION.$dbname.HERMES_SCROLL_EXTENSION;
		#	return $dbfile;
		#}
		#else /*!($multiple === FALSE)*/{
		#	$set = array();
		#	foreach($list as $i=>$dbname){
		#		$set[$i] = HERMES_SCROLL_LOCATION.str_replace(HERMES_SCROLL_FORMAT_DROP, '', $dbname).HERMES_SCROLL_EXTENSION;
		#	}
		#	return $set;
		#}
	}
	public function listScrolls($a=FALSE, $b=FALSE){
		if(is_array($a)){
			$list = array();
			foreach($a as $c){
				$list = array_merge($list, self::listScrolls($c));
			}
		}
		else{
		#if($b == FALSE && is_int($a)){ /*get year-list*/ $year = $a; }
		#elseif($b == FALSE && is_string($a)){ /*get pattern 'Y-m' remapped on HERMES_SCROLL_FORMAT*/ }
		
			#gets all valid Scrolls
			$list = @scandir(HERMES_SCROLL_LOCATION); /*fix*/ if(!is_array($list)){ $list = array(); }
			$format = HERMES_SCROLL_FORMAT;
			if(isset($condition) && is_array($condition)){foreach($condition as $t=>$v){
				if(is_array($v)){ $format = str_replace($t, '('.implode('|', $v).')', $format); }
				else{ $format = str_replace($t, $v, $format); }
			}}
			foreach($list as $i=>$f){
				if(!(preg_match('#'.preg_replace('#[a-z]#i', '[0-9]+', $format).HERMES_SCROLL_EXTENSION.'#i', $f) || preg_match('#'.preg_replace('#[a-z]#i', '[0-9]+', str_replace(str_replace('0', 'x', HERMES_SCROLL_FORMAT_DROP), '', $format)).HERMES_SCROLL_EXTENSION.'#i', $f))){ unset($list[$i]); }
			}
		}
		return $list;
	}
	public function getLatestScrollID($list=FALSE, $check=FALSE, $result=NULL){ #$result = (array) EMPTY | NULL
		if($list===FALSE){ $list = self::listScrolls();}
		#/*debug*/ print '<!-- Hermes::getLatestScrollID \$list '.print_r($list, TRUE).' -->'."\n";
		if(!is_array($list)){
			return FALSE;
		} elseif(count($list) == 0){  /*none existing means start current date*/ //str_replace(HERMES_SCROLL_FORMAT_DROP, '', date(HERMES_SCROLL_FORMAT));
			$list = array(date(str_replace(str_replace('0', 'x', HERMES_SCROLL_FORMAT_DROP), '', HERMES_SCROLL_FORMAT)).HERMES_SCROLL_EXTENSION );
		}
		#/*debug*/ print '<!-- \"\" \$list  '.print_r($list, TRUE).' -->'."\n";
		$set = array();
		foreach($list as $i=>$scroll){
			$set[$i] = self::_read_scroll_name($scroll);
		}
		/*
		Y.m...d
		 .n
		   ...j
		 .....z
		 ...W.N
		     .w
		       .B____
		       .H i s
		       .G
		............u
		*/
		/*temporarily; assume scandir list alphabetically or by lastmodification*/ $latest = end($set);
		
		if($check===TRUE && file_exists(HERMES_SCROLL_LOCATION.$latest['original'].HERMES_SCROLL_EXTENSION)){
			$size = filesize(HERMES_SCROLL_LOCATION.$latest['original'].HERMES_SCROLL_EXTENSION);
			if($size > HERMES_SCROLL_SIZE_LIMIT){ $latest['x']++; $latest['action'] = 'new'; }
			$latest['original'] = HERMES_SCROLL_FORMAT;
			foreach($latest as $t=>$v){
				if(strlen($t) == 1){ $latest['original'] = str_replace($t, $v, $latest['original']); }
			}
			$latest['original'] = str_replace(HERMES_SCROLL_FORMAT_DROP, '', $latest['original']);
		}
		#/*debug*/ return array_merge($set, array('latest'=>$latest));
		return (is_array($result) ? $latest : $latest['original'] );
	}
	public function getCurrentScrollID(){
		$current = array(); $bool = TRUE;
		$latest = self::getLatestScrollID(FALSE, TRUE, array());
		#/*debug*/ print '<!-- Hermes::getCurrentScrollID \$latest '.print_r($latest, TRUE).' -->'."\n";
		if($bool == is_array($latest)){ foreach($latest as $t=>$v){
			if(strlen($t)==1 && $t!='x'){ #in_array($t, array('Y','m'))
				$bool = ($bool && ($v == date((string) $t)));
			} elseif($t == 'original'){
				if(strlen($v) < 1){ $bool = FALSE; }
			}
		} }
		
		if($bool == TRUE){ $current = $latest; }
		else{ $current['original'] = str_replace(array(HERMES_SCROLL_FORMAT_DROP, str_replace('0', 'x', HERMES_SCROLL_FORMAT_DROP)), '', date(HERMES_SCROLL_FORMAT)); }
		#/*debug*/ return array('scroll'=> $current['original'], 'current'=>$current, 'latest'=>$latest, 'bool'=>$bool);
		return $current['original'];
	}
	public function getCurrentScrollFile(){
		return self::getScrollFile();
	}
	public function getScrollFile($scroll=NULL){
		if($scroll === NULL){ $scroll = self::getCurrentScrollID(); }
		return HERMES_SCROLL_LOCATION.$scroll.HERMES_SCROLL_EXTENSION;
	}
	private /*array*/ function _read_scroll_name($scroll){
		$i = -1; $set[$i] = array();
		$set[$i] = array('original'=>preg_replace('#'.HERMES_SCROLL_EXTENSION.'$#', '', $scroll));
		if(!preg_match('#^'.preg_replace('#[a-z]#i', '[0-9]+', HERMES_SCROLL_FORMAT).'('.HERMES_SCROLL_EXTENSION.')?'.'$#', $scroll)) /*assume with HERMES_SCROLL_FORMAT_DROP*/ {
			$format = str_replace(str_replace('0', 'x', HERMES_SCROLL_FORMAT_DROP), '', HERMES_SCROLL_FORMAT);
			$set[$i]['x'] = 0;
		}
		else { $format = HERMES_SCROLL_FORMAT; }
		$pattern = preg_replace('#[a-z]#i', '([0-9]+)', self::_preg_unbracked($format)).'('.HERMES_SCROLL_EXTENSION.')?'; 
		$matchstr = preg_replace('#[^a-z]#i', '', $format);
		preg_match('#^'.$pattern.'$#', $scroll, $dummy);
		#/*debug*/ $set[$i]['dummy'] = $dummy; $set[$i]['pattern'] = $pattern; $set[$i]['matchstr'] = $matchstr; 
		foreach($dummy as $d=>$v){
			if(isset($matchstr{$d-1})){ $set[$i][$matchstr{$d-1}] = $v; }
		}
		return $set[$i];
	}
	
	
	
	/**************************************************
	 * LIBRARY
	 **************************************************/

	function escape_preg_chars($str, $qout=array(), $merge=FALSE){
		if($merge !== FALSE){
			$qout = array_merge(array('\\'), (is_array($qout) ? $qout : array($qout)), array('[',']','(',')','{','}','$','+','^','-','#'));
			#/*debug*/ print_r($qout);
		}
		if(is_array($qout)){
			$i = 0;
			foreach($qout as $k=>$v){
				if($i == $k){
					$str = str_replace($v, '\\'.$v, $str);
				} else{
					$str = str_replace($k, $v, $str);				
				}
				$i++;
			}
		}
		else{ $str = str_replace($qout, '\\'.$qout, $str); }
		return $str;
	}
	private function _preg_unbracked($str){
		return self::escape_preg_chars($str, array(), TRUE);
	}
	public function json_decode($json, $assoc=/*FALSE*/TRUE){ /*patching the UTF8 freakability of json_decode */
		$set = json_decode(utf8_encode($json), $assoc);
		if($assoc === FALSE){
			#utf8_decode (object->key)
		}
		else{
			if(is_array($set)){ foreach($set as $key=>$value){
				$set[$key] = utf8_decode($value);
			}}
		}
		return $set;
	}
	public function human_readable_json_last_error(){
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				return 'No errors';
			break;
			case JSON_ERROR_DEPTH:
				return 'Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				return 'Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				return 'Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				return 'Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				return 'Unknown error';
			break;
		}
	}
}

if(!class_exists('Xnode')){
	class Xnode {
		public function large_base_convert ($numstring, $frombase, $tobase, $bitlength=0) {
			if($bitlength===0){ $bitlength = strlen(self::large_base_convert(self::large_base_convert($frombase-1, 10, $frombase, -1), $frombase, $tobase, -1)); }
			$numstring .= ''; /*forced string fix*/
			$chars = "0123456789" #10
				."abcdefghij" #20
				."klmnopqrst" #30
				."uvwxyzABCD" #40
				."EFGHIJKLMN" #50
				."OPQRSTUVWX" #60
				."YZ-_+!@$%~" #70 (trustworthy up to base62 (10+26+26), backwards-compatible to base70 (pre Xnode v2.0 RC047) )
				."\"#&'()*,./" #80
				.":;<=>?[\\]^" #90
				."`{|}€ƒ…†‡Š" #100
				."ŒŽ•™šœžŸ¡¢" #110
				."£¤¥§©«¬®°±" #120
				."µ¶»¼½¾¿ÆÐ×" #130
				."Þßæçð÷ø \t\n"; #137~140
			$tostring = substr($chars, 0, $tobase);

			$original = $numstring;
			/*CaseClass-fix*/ if($frombase<=36){$numstring = strtolower($numstring);}
		
			$length = strlen($numstring);
			$result = '';
			for ($i = 0; $i < $length; $i++) {
				$number[$i] = strpos($chars, $numstring{$i});
			}
			do {
				$divide = 0;
				$newlen = 0;
				for ($i = 0; $i < $length; $i++) {
					$divide = $divide * $frombase + $number[$i];
					if ($divide >= $tobase) {
						$number[$newlen++] = (int)($divide / $tobase);
						$divide = $divide % $tobase;
					} elseif ($newlen > 0) {
						$number[$newlen++] = 0;
					}
				}
				$length = $newlen;
				$result = $tostring{$divide} . $result;
			}
			while ($newlen != 0);

			/*CaseClass-fix*/ if($frombase<=36 && $numstring!=$original){$result = strtoupper($result);}

			/*fulllength compatibility-fix*/ if($bitlength > 0 && $bitlength >= strlen((string) $result) ){ $result = str_repeat($chars{1}, $bitlength-strlen((string) $result)).((string) $result); }

			return (string) $result;
		}
	}
}
?>
