<?php
/*************
*  SETTINGS  *
*************/
@define('HERMES_SCROLL_LOCATION', /*file:*/ dirname(__FILE__). DIRECTORY_SEPARATOR .'db'. DIRECTORY_SEPARATOR );
@define('HERMES_SCROLL_EXTENSION', '.hermes'); #pre-0.3.0 this was .json-array.txt; post-0.3.0 it will be .hermes
@define('HERMES_SCROLL_SIZE_LIMIT', 1024*1024*1 /*=1Mb*/);
@define('HERMES_SCROLL_FORMAT', 'Y-m[x]');
@define('HERMES_SCROLL_FORMAT_DROP', '[0]');
@define('HERMES_ENCRYPTED_RECORD', FALSE);
@define('HERMES_IDENTITY_BASE', 128); #pre-0.3.0 default: 75; post-0.3.0 default: 128 with " fix to ×
@define('HERMES_BASE_FIX_CHARACTER', '×'); #replaces " by ×


/*************
*   FIXES    *
*************/
#This is only required for my personal flavor of Hermes; feel free to remove this line
if(file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'Xnode.php')){ require_once(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'Xnode.php'); }
?>
