<?php

$dir = '';
if (strpos(__DIR__, '/helpers') !== false) {
   $dir = str_replace("/helpers", "", __DIR__);
}
if (strpos(__DIR__, '\helpers') !== false) {
   $dir = str_replace("\helpers", "", __DIR__);
}
require_once $dir . '/notif/notifWA.php';
require_once $dir . '/core/core.php';

require_once $dir . '/sql_engine.php';

$serverName = $_SERVER['SERVER_NAME'];
if($serverName == '10.0.2.2'){
    $globalVar = $GLOBALS['localhost'];
}
else if ($serverName == 'localhost') {
    // local
    $API_URL = "//localhost". DIR_API_LOCAL;
    $globalVar = $GLOBALS[$serverName];
    $URL = "//localhost:3000/#/";
} else {
    // production
    $API_URL = "//".$serverName. DIR_API_PRO;
    $pathSub = $_SERVER['PHP_SELF'];
    $delIndex = str_replace("/index.php", "", $pathSub);
    $delSlash = str_replace("/","", $delIndex);
    $globalVar = $GLOBALS[$delSlash];
    $URL = "//".$_SERVER['SERVER_NAME'];
}