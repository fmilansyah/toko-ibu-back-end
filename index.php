<?php
header("Access-Control-Allow-Headers: token, Access-Control-Allow-Headers, api-key, client-id, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization, Access-Control-Allow-Methods, Access-Control-Allow-Origin");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header("Access-Control-Allow-Headers: token, Access-Control-Allow-Headers, api-key, client-id, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization, Access-Control-Allow-Methods, Access-Control-Allow-Origin");
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("HTTP/1.1 200 OK");
    die();
}

require_once __DIR__ . '/core/flight/Flight.php';
require_once __DIR__ . '/api.php';
// ini_set('memory_limit', '-1');

Flight::route('GET /getdatabarang', 'getDataBarang');
Flight::route('POST /getdetailbarang', 'getDetailBarang');
Flight::route('POST /tambahdatabarang', 'tambahDataBarang');
Flight::route('POST /ubahdatabarang', 'ubahDataBarang');
Flight::route('POST /deletedatabarang', 'deleteDataBarang');

Flight::start();
?>
