<?php

/*
 * All database connection variables
 */

require_once __DIR__ .'/config.php';

$serverName = $_SERVER['SERVER_NAME'];
if($serverName == '10.0.2.2'){
    $globalVar = $GLOBALS['localhost'];
}
else if ($serverName == 'localhost') {
    // localhost
    $globalVar = $GLOBALS[$serverName];
} else {
    $pathSub = $_SERVER['PHP_SELF'];
    $delIndex = str_replace("/index.php", "", $pathSub);
    $delSlash = str_replace("/","", $delIndex);
    // $globalVar = $GLOBALS[$delSlash];
    $globalVar = $GLOBALS['api'];
    echo $serverName;
    // echo $delSlash;
    // echo $globalVar;

    // if(strpos($delSlash, 'notification-midtrans')){
    //     // config midtrans
    //     $pathMidtrans = str_replace("notification-midtrans.php","", $delSlash);
    //     if ($pathMidtrans == "dhammagrahaAPI-v.1.3.0") {
    //         // config midtranslocal ngrok
    //         $globalVar = $GLOBALS['dhammagrahaAPI'];
    //     } else {
    //         $globalVar = $GLOBALS[$pathMidtrans];
    //     }
    // } else {
    //     // config midtrans server
    //     $globalVar = $GLOBALS[$delSlash];
    // }
}

define('DB_USER', $globalVar['DB_USER']); // db user
define('DB_PASSWORD', $globalVar['DB_PASSWORD']); // db password (mention your db password here)
define('DB_DATABASE', $globalVar['DB_DATABASE']); // database name
define('DB_SERVER', $globalVar['DB_SERVER']); // db server

// define('DB_USER', 'id19262965_maudhio'); // db user
// define('DB_PASSWORD', '5jUNq^5yr*Vp(3xlr1E#'); // db password (mention your db password here)
// define('DB_DATABASE', 'id19262965_reactapi'); // database name
// define('DB_SERVER', 'localhost'); // db server

define('WEB_SERVER', $globalVar['WEB_SERVER']); // web server
// define('DIR_API', $globalVar['DIR_API']); // directori API Local
define('DIR_API_LOCAL', $globalVar['DIR_API_LOCAL']); // directori API Local
define('DIR_API_PRO', $globalVar['DIR_API_PRO']); // directori API Production
define('DIR_API', $globalVar['DIR_API']); // directori API Production
define('PATH_FRONTEND', $globalVar['PATH_FRONTEND']); // direct email 

?>