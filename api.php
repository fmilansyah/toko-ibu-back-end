<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: TOKEN, HTTP_TOKEN, HTTP_X_API_KEY, HTTP_X_CLIENT_ID, X-api-key, X-client-key, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: TOKEN, HTTP_TOKEN, HTTP_X_API_KEY, HTTP_X_CLIENT_ID, X-api-key, X-client-key, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

ini_set('memory_limit', '-1');

require_once __DIR__ . '/sql_engine.php';

function getDataBarang(){
    echo getDataBarangSQL();
}

function tambahDataBarang(){
    if (isset($_POST['kd_barang'], $_POST['nama'], $_POST['ukuran'])) {
        $kd_barang = htmlspecialchars($_POST['kd_barang']);
        $nama = htmlspecialchars($_POST['nama']);
        $ukuran = json_decode($_POST['ukuran'], true);
        
        $listFile = false;
        if(isset($_FILES['images'])){
            $listFile = $_FILES['images'];
        }

        echo tambahDataBarangSQL($kd_barang, $nama, $ukuran, $listFile);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function ubahDataBarang(){
    if (isset($_POST['kd_barang'], $_POST['nama'], $_POST['ukuran'], $_POST['hapus_ukuran'], $_POST['hapus_file'])) {
        $kd_barang = htmlspecialchars($_POST['kd_barang']);
        $nama = htmlspecialchars($_POST['nama']);
        $ukuran = json_decode($_POST['ukuran'], true);
        $hapus_ukuran = json_decode($_POST['hapus_ukuran'], true);
        $hapus_file = json_decode($_POST['hapus_file'], true);

        $listFile = false;
        if(isset($_FILES['images'])){
            $listFile = $_FILES['images'];
        }

        echo ubahDataBarangSQL($kd_barang, $nama, $ukuran, $hapus_ukuran, $hapus_file, $listFile);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function deleteDataBarang(){
    if (isset($_POST['kd_barang'])) {
        $kd_barang = htmlspecialchars($_POST['kd_barang']);
        echo deleteDataBarangSQL($kd_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getDetailBarang(){
    if (isset($_POST['kd_barang'])) {
        $kd_barang = htmlspecialchars($_POST['kd_barang']);
        echo getDetailBarangSQL($kd_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}


?>