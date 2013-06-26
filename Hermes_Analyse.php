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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Hermes_Filter.php');

class Hermes_Analyse extends Hermes_Filter{

	/*string*/ function get_browser($buffer=FALSE){ #mozilla|gecko||applewebkit
		if(preg_match_all("#((firefox|msie|chrome|safari|opera|mozilla)([/ ]([0-9_.]+[b]?))?)#i", $buffer /*self::get_HTTP_USER_AGENT($buffer)*/, $x)){ 
			#switch(strtolower($x[1])){
			#	case 'mozilla': return ''; break; 
			#	default: return strtolower($x[1]);
			#}
			return implode(' ', $x[1]);
		}
		else{ return FALSE; }
	}
	/*string*/ function get_platform($buffer=FALSE){
		if(preg_match_all("#(linux( x86_64| i686)?|ubuntu|Windows NT [0-9.]+|win9[58]|Windows 98|Mac OS X [0-9_.]+|iPhone OS [0-9_.]+|iOS [0-9_.]|nokia[0-9]+|samsung[^ ]+)#i", $buffer /*self::get_HTTP_USER_AGENT($buffer)*/, $x)){
			return implode('; ', $x[1]);
		}
		else{ return FALSE; }
	
	}
	/*string*/ function get_bot($buffer=FALSE){}
	/*string*/ function get_CC($buffer=FALSE){ return self::get_REMOTE_ADDR_CC($buffer); }
	/*string|array*/ function get_language($buffer=FALSE, $multiple=TRUE){
		if($multiple === TRUE){ return array_keys(self::get_HTTP_ACCEPT_LANGUAGE($buffer, TRUE)); }
		else{ return /*assume first has highest value*/ reset(array_keys(self::get_HTTP_ACCEPT_LANGUAGE($buffer, TRUE))); }
	}
	/*bool*/ function is_bot($buffer=FALSE){
		return preg_match('/(crawler|spider|google|bot|facebookexternalhit|yahoo|URL Resolver|UnwindFetchor|urllib|metauri|webagent|[\+]?http[:][\/]{2}|ips-agent|Babya Discoverer|Postrank|TwitterFeed|funwebproducts|NetcraftSurveyAgent|TalkTalk|Virus|marks|httpclient|deepnet|ICS|PycURL|[\:]{2}|[@]|webster)/i', self::get_HTTP_USER_AGENT($buffer));
	}
	/*bool*/ function is_real($buffer=FALSE){}
	/*bool*/ function is_author($buffer=FALSE){ /*if IP listed as author, or if authenticated as author, e.g. login in WordPress */ }
}

		#	preg_match_all('#((.NET |WINDOWS |MAC OS |iPhone|MEDIA CENTER |CPU )?[^/ \[\(;,:]+[/ ][0-9._]+|windows|linux|ipad|ios|apple|firefox|mozilla|safari|applewebkit|gecko|msie)#i', $uset[1], $uuset);
		#	foreach($uuset[1] as $v){if(!isset($agents[$v])){ $agents[$v] = 0; } $agents[$v] += 1; }
		#} else { $currhua = ''; }
?>