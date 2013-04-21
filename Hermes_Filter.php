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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Hermes.php');
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Hermes.settings.php');

/* The variable $buffer can be of the following types:
 * - (bool) FALSE			: use $this->buffer, or it is the result of an error
 * - (string) %identity%	: an identity
 * - (string) {JSON}		: the encoded JSON of an HERMES_RECORD
 * - (array) $set			: the decoded JSON of an HERMES_RECORD
 * - (object) HERMES		: the current $__HERMES_RECORD
 */

class Hermes_Filter{
	var $buffer;
	var $scrolls = NULL;
	function Hermes_Filter($buffer=NULL){
		if($buffer !== NULL){ self::set($buffer); }
	}
	function get_buffer(){ return $this->buffer; }
	
	function current($fullpath=FALSE){
		return ($fullpath!==FALSE ? Hermes::getCurrentScrollFile() : Hermes::getLatestScrollID() );
	}
	function set($buffer){
		/*fix*/ $buffer = preg_replace("#[,]\s+$#i", "", $buffer);
		$this->buffer = $buffer;
	}
	function get($variable=TRUE, $buffer=FALSE, $search=FALSE){
		if($buffer === FALSE){ $buffer = $this->buffer; }
		if($search === TRUE && self::get($variable, $buffer) === FALSE){
			#search in the self::current(TRUE) for the first record of self::get('identity', $buffer);
			return self::get($variable, self::first_scroll($buffer));
		}
		if(is_array($buffer)){$set = $buffer;}
		elseif( /*is identity*/ FALSE ){ $set = Hermes::json_decode(self::last_record($buffer), TRUE); }
		elseif( /*is JSON*/ TRUE ){ $set = Hermes::json_decode($buffer, TRUE); }
		else{ /*error*/ $set = array(); }
		
		if($variable===TRUE){ return $set; }
		elseif(isset($set[$variable])){ return $set[$variable]; }
		else{ return FALSE; }
	}
	function get_identity($buffer=FALSE){
		if($buffer === TRUE){ return Hermes::getIdentity(); }
		else{ return self::get('identity', $buffer); }
	}
	/*string*/ function get_when($buffer=FALSE){ return self::get('when', $buffer); }
	/*string*/ function get_HTTP_ACCEPT_LANGUAGE($buffer=FALSE, $assoc=FALSE){
		$HAL = self::get('HTTP_ACCEPT_LANGUAGE', $buffer, TRUE);
		if($assoc !== FALSE){
			$set = explode(',', $HAL);
			$r = array(); $i = 0;
			foreach($set as $lvar){
				if(preg_match("#^([a-z-]+);q=([0][.][0-9]+)$#i", $lvar, $buff)){
					$r[$buff[1]] = (double) $buff[2];
					if(preg_match("#^([a-z]+)-([a-z]+)$#i", $buff[1], $biff)){
						if(!isset($r[$biff[1]])){ $r[$biff[1]] = (double) $buff[2]; }
					}
				}
				else{ $r[$lvar] = ($i == 0 ? (double) '1.0' : $lvar); }
				$i++;
			}
			return $r;
		}
		else{ return $HAL; }
	}
	/*string: IP*/ function get_REMOTE_ADDR($buffer=FALSE){ return self::get('REMOTE_ADDR', $buffer, TRUE); }
	/*string*/ function get_REMOTE_ADDR_CC($buffer=FALSE){
		$value = self::get('REMOTE_ADDR_CC', $buffer, TRUE);
		if(!$value && function_exists('geoip_country_code_by_name')){
			$value = /*@ hides notice-warning on local addresses*/ @geoip_country_code_by_name(self::get_REMOTE_ADDR($buffer));
		}
		return $value;
	}
	/*string*/ function get_HTTP_USER_AGENT($buffer=FALSE){ return self::get('HTTP_USER_AGENT', $buffer, TRUE); }
	/*string*/ function get_HTTP_REFERER($buffer=FALSE){ return self::get('HTTP_REFERER', $buffer, TRUE); }
	
	/*array*/ function list_scrolls($buffer=FALSE){
		if(is_array($this->scrolls)){ return $this->scrolls; }
		$identity = self::get_identity($buffer);
		$scroll = self::current(TRUE);
		$cache = /*array*/ file($scroll);
		$list = array();
		$pattern = '#"identity": "'.Hermes::escape_preg_chars($identity, array(), TRUE).'"#';
		#/*debug*/ print_r($pattern);
		foreach($cache as $i=>$scr){
			if(preg_match($pattern, $scr)){
				$list[$i] = $scr;
				/*fix*/ $list[$i] = preg_replace("#[,]\s+$#i", "", $list[$i]);
			}
		}
		$this->scrolls = $list;
		return $list;
	}
	/*dummy*/ function list_records($buffer=FALSE){ return self::list_scrolls($buffer); }
	/*JSON*/ function last_scroll($buffer=FALSE){ return end(self::list_scrolls($buffer)); }
	/*dummy*/ function last_record($buffer=FALSE){ return self::last_scroll($buffer); }
	/*JSON*/ function first_scroll($buffer=FALSE){ return reset(self::list_scrolls($buffer)); }
	/*dummy*/ function first_record($buffer=FALSE){ return self::first_scroll($buffer); }
	/*int*/ function scroll_count($buffer=FALSE){ return count(self::list_scrolls($buffer)); }
}
?>