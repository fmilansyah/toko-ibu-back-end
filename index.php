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

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/core/flight/Flight.php';
require_once __DIR__ . '/api.php';
// ini_set('memory_limit', '-1');

// Flight::set('flight.log_errors', true);

// Flight::set('flight.base_url', 'http://192.168.231.90/');

// ------------------ BARANG
Flight::route('GET /getdatabarang', 'getDataBarang');
Flight::route('GET /getdatabarangperkategori', 'getDataBarangPerKategori');
Flight::route('POST /getdetailbarang', 'getDetailBarang');
Flight::route('POST /tambahdatabarang', 'tambahDataBarang');
Flight::route('POST /ubahdatabarang', 'ubahDataBarang');
Flight::route('POST /deletedatabarang', 'deleteDataBarang');
Flight::route('GET /getbarangterbaru', 'getBarangTerbaru');

// ------------------ KATEGORI
Flight::route('GET /getkategori', 'getKategori');
Flight::route('GET /getkategoridanbarang', 'getKategoriDanBarang');
Flight::route('POST /tambahkategori', 'tambahKategori');
Flight::route('POST /ubahkategori', 'ubahKategori');
Flight::route('POST /deletekategori', 'deleteKategori');

// ------------------ KATEGORI BARANG
Flight::route('POST /getkategoribarang', 'getKategoriBarang');
Flight::route('POST /setkategoribarang', 'setKategoriBarang');

// ----------------- USER
Flight::route('POST /daftaruser', 'daftarUser');
Flight::route('POST /ubahuser', 'ubahUser');
Flight::route('POST /ubahstatususer', 'ubahStatusUser');
Flight::route('POST /login', 'login');

// ----------------- KERANJANG
Flight::route('POST /getdatakeranjang', 'getDataKeranjang');
Flight::route('POST /tambahkeranjang', 'tambahKeranjang');
Flight::route('POST /hapuskeranjang', 'hapusKeranjang');

// ---------------- ORDER
Flight::route('POST /orderbarang', 'orderBarang');
Flight::route('POST /kirimbarang', 'kirimBarang');
Flight::route('POST /selesaiorder', 'selesaiOrder');
Flight::route('POST /getlistorder', 'getListOrder');
Flight::route('POST /getuserorder', 'getUserOrder');

// ---------------- MIDTRANS
Flight::route('POST /midtrans-createtoken', 'midtransCreateToken');

Flight::start();
?>
