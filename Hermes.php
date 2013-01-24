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

#function Hermes($record=array()){
#	global $__HERMES_RECORD;
#	if(!isset($__HERMES_RECORD) || !(is_object($__HERMES_RECORD) && get_class($__HERMES_RECORD) =='Hermes' ) ){
#		$__HERMES_RECORD = new Hermes();
#	}
#	$__HERMES_RECORD->Messenger($record);
#}
class Hermes{
	public function Version($f=FALSE){ return '0.3.0'; }
	public function Product_url($u=FALSE){ return ($u === TRUE ? "https://github.com/sentfanwyaerda/Hermes" : "http://sent.wyaerda.org/Hermes/?version=".self::Version(TRUE).'&license='.str_replace(' ', '+', self::License()) );}
	public function Product($full=FALSE){ return "Hermes".(!($full===FALSE) ? " ".self::version(TRUE) : NULL); }
	public function License($with_link=FALSE){ return ($with_link ? '<a href="'.self::License_url().'">' : NULL).'cc-by-nd 3.0'.($with_link ? '</a>' : NULL); }
	public function License_url(){ return 'http://creativecommons.org/licenses/by-nd/3.0/'; }
	public function Product_base(){ return dirname(__FILE__).DIRECTORY_SEPARATOR; }
	public function Product_file($full=FALSE){ return ($full ? self::Product_base() : NULL).basename(__FILE__); }
	
	
	private $_record = array();
	public function Hermes(){
		$this->_record = array(/**/);
	}
	public function Messenger($record=array()){
		$this->_record = array_merge($record, $this->_record);
	}
	public /*bool*/ function deliver(){
		/*(re)writes log entry*/
		if(HERMES_ENCRYPTED_RECORD){ /*encrypt record before writing*/ }
		return FALSE;
	}
	
	public function getIdentity($a=FALSE, $b=FALSE, $c=FALSE, $d=FALSE){
		$a = ($a === FALSE ? $_SERVER['REMOTE_ADDR']: $a);
		$b = ($b === FALSE ? $_SERVER['REMOTE_HOST']: $b);
		$c = ($c === FALSE ? $_SERVER['HTTP_USER_AGENT']: $c);
		$d = ($d === FALSE ? $_SERVER['HTTP_ACCEPT_LANGUAGE']: $d);		
		$hash = md5($c.$a.$b.$d);
		$identity = Xnode::large_base_convert($hash,16,HERMES_IDENTITY_BASE);
		/*"-fix*/ $identity = str_replace('"', HERMES_BASE_FIX_CHARACTER, $identity);
		return $identity;
	}
	public function getScroll($current=TRUE, $multiple=FALSE){
		if($current == TRUE && $multiple == FALSE){ return self::getLatestScrollID(); }
		$dbname = date(HERMES_SCROLL_FORMAT);
		$list = self::listScrolls(str_replace(HERMES_SCROLL_FORMAT_DROP, '', str_replace('x', '0', $dbname)));
		
		if($multiple === FALSE){
			$dbname = str_replace(HERMES_SCROLL_FORMAT_DROP, '', $dbname);
			$dbfile = HERMES_SCROLL_LOCATION.$dbname.HERMES_SCROLL_EXTENSION;
			return $dbfile;
		}
		else /*!($multiple === FALSE)*/{
			$set = array();
			foreach($list as $i=>$dbname){
				$set[$i] = HERMES_SCROLL_LOCATION.str_replace(HERMES_SCROLL_FORMAT_DROP, '', $dbname).HERMES_SCROLL_EXTENSION;
			}
			return $set;
		}
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
			$list = scandir(HERMES_SCROLL_LOCATION);
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
		if(!is_array($list)){ return FALSE; }
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
	private /*array*/ function _read_scroll_name($scroll){
		$i = -1; $set[$i] = array();
		$set[$i] = array('original'=>preg_replace('#'.HERMES_SCROLL_EXTENSION.'$#', '', $scroll));
		if(!preg_match('#^'.preg_replace('#[a-z]#i', '[0-9]+', HERMES_SCROLL_FORMAT).'('.HERMES_SCROLL_EXTENSION.')?'.'$#', $scroll)) /*assume with HERMES_SCROLL_FORMAT_DROP*/ {
			$format = str_replace(str_replace('0', 'x', HERMES_SCROLL_FORMAT_DROP), '', HERMES_SCROLL_FORMAT);
			$set[$i]['x'] = 0;
		}
		else { $format = HERMES_SCROLL_FORMAT; }
		$pattern = preg_replace('#[a-z]#i', '([0-9]+)', $format).'('.HERMES_SCROLL_EXTENSION.')?'; 
		$matchstr = preg_replace('#[^a-z]#i', '', $format);
		preg_match('#^'.$pattern.'$#', $scroll, $dummy);
		#/*debug*/ $set[$i]['dummy'] = $dummy; $set[$i]['pattern'] = $pattern; $set[$i]['matchstr'] = $matchstr; 
		foreach($dummy as $d=>$v){
			if(isset($matchstr{$d-1})){ $set[$i][$matchstr{$d-1}] = $v; }
		}
		return $set[$i];
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
