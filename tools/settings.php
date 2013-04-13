<?php
$domain = ($_SERVER["SERVER_PROTOCOL"] == 'HTTP/1.1' ? 'http' : 'https').'://'.$_SERVER["SERVER_NAME"].'/';
$author_ipset = array('82.169.112.26','193.164.216.100');
if(isset($_GET['action']) && in_array($_GET['action'], array('minimize','min') )){ $minimize = TRUE; }
?>