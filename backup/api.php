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

function getKategoriDanBarang(){
    echo getKategoriDanBarangSQL();
}

function getKategori(){
    echo getKategoriSQL();
}

function setKategoriBarang(){
    if (isset($_POST['kategori_barang'], $_POST['hapus_kategori_barang'])) {
        
        $kategori_barang = json_decode($_POST['kategori_barang'], true);
        $hapus_kategori_barang = json_decode($_POST['hapus_kategori_barang'], true);

        echo setKategoriBarangSQL($kategori_barang, $hapus_kategori_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function tambahDataBarang(){
    if (isset($_POST['nama'], $_POST['kd_kategori'], $_POST['ukuran'])) {
        $nama = htmlspecialchars($_POST['nama']);
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        $ukuran = json_decode($_POST['ukuran'], true);
        
        $listFile = false;
        if(isset($_FILES['images'])){
            $listFile = $_FILES['images'];
        }

        echo tambahDataBarangSQL($nama, $kd_kategori, $ukuran, $listFile);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function orderBarang(){
    if (isset($_POST['kd_user'], $_POST['orders'], $_POST['jenis_order'], $_POST['jasa_pengiriman'], $_POST['jenis_pengiriman'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);
        $jenis_order = htmlspecialchars($_POST['jenis_order']);
        $orders = json_decode($_POST['orders'], true);

        $jasa_pengiriman = htmlspecialchars($_POST['jasa_pengiriman']);
        $jenis_pengiriman = htmlspecialchars($_POST['jenis_pengiriman']);

        echo orderBarangSQL($kd_user, $jenis_order, $orders, $jasa_pengiriman, $jenis_pengiriman);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function kirimBarang(){
    if (isset($_POST['kd_order'], $_POST['no_resi'])) {
        $kd_order = htmlspecialchars($_POST['kd_order']);
        $no_resi = htmlspecialchars($_POST['no_resi']);

        echo kirimBarangSQL($kd_order, $no_resi);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function selesaiOrder(){
    if (isset($_POST['kd_order'])) {
        $kd_order = htmlspecialchars($_POST['kd_order']);

        echo selesaiOrderSQL($kd_order);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}




function ubahUser(){
    if (isset($_POST['kd_user'], $_POST['nama'], $_POST['no_telepon'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);
        $nama = htmlspecialchars($_POST['nama']);
        $no_telepon = htmlspecialchars($_POST['no_telepon']);

        $email = htmlspecialchars($_POST['email']);
        $alamat = htmlspecialchars($_POST['alamat']);
        $kode_pos = htmlspecialchars($_POST['kode_pos']);

        $foto_profil = false;
        if(isset($_FILES['foto_profil'])){
            $foto_profil = $_FILES['foto_profil'];
        }

        echo ubahUserSQL($kd_user, $nama, $no_telepon, $email, $alamat, $kode_pos, $foto_profil);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function daftarUser(){
    if (isset($_POST['nama'], $_POST['no_telepon'], $_POST['password'])) {
        $nama = htmlspecialchars($_POST['nama']);
        $no_telepon = htmlspecialchars($_POST['no_telepon']);
        $password = md5(htmlspecialchars($_POST['password']));

        echo daftarUserSQL($nama, $no_telepon, $password);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function tambahKeranjang(){
    if (isset($_POST['kd_user'], $_POST['kd_detail_barang'], $_POST['jumlah_barang'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);
        $kd_detail_barang = htmlspecialchars($_POST['kd_detail_barang']);
        $jumlah_barang = htmlspecialchars($_POST['jumlah_barang']);

        echo tambahKeranjangSQL($kd_user, $kd_detail_barang, $jumlah_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getDataKeranjang(){
    if (isset($_POST['kd_user'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);

        echo getDataKeranjangSQL($kd_user);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function hapusKeranjang(){
    if (isset($_POST['kd_keranjang'])) {
        $kd_keranjang = htmlspecialchars($_POST['kd_keranjang']);

        echo hapusKeranjangSQL($kd_keranjang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}



function login(){
    if (isset($_POST['no_telepon'], $_POST['password'])) {
        $no_telepon = htmlspecialchars($_POST['no_telepon']);
        $password = md5(htmlspecialchars($_POST['password']));

        echo loginSQL($no_telepon, $password);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}


function tambahKategori(){
    if (isset($_POST['nama'], $_POST['keterangan'])) {
        $nama = htmlspecialchars($_POST['nama']);
        $keterangan = htmlspecialchars($_POST['keterangan']);
        
        echo tambahKategoriSQL($nama, $keterangan);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function ubahDataBarang(){
    if (isset($_POST['kd_barang'], $_POST['nama'], $_POST['kd_kategori'], $_POST['ukuran'], $_POST['hapus_ukuran'], $_POST['hapus_file'])) {
        $kd_barang = htmlspecialchars($_POST['kd_barang']);
        $nama = htmlspecialchars($_POST['nama']);
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        $ukuran = json_decode($_POST['ukuran'], true);
        $hapus_ukuran = json_decode($_POST['hapus_ukuran'], true);
        $hapus_file = json_decode($_POST['hapus_file'], true);

        $listFile = false;
        if(isset($_FILES['images'])){
            $listFile = $_FILES['images'];
        }

        echo ubahDataBarangSQL($kd_barang, $nama, $kd_kategori, $ukuran, $hapus_ukuran, $hapus_file, $listFile);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function ubahKategori(){
    if (isset($_POST['kd_kategori'], $_POST['nama'], $_POST['keterangan'])) {
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        $nama = htmlspecialchars($_POST['nama']);
        $keterangan = htmlspecialchars($_POST['keterangan']);
        
        echo ubahKategoriSQL($kd_kategori, $nama, $keterangan);
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

function deleteKategori(){
    if (isset($_POST['kd_kategori'])) {
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        echo deleteKategoriSQL($kd_kategori);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getKategoriBarang(){
    if (isset($_POST['kd_kategori'])) {
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        echo getKategoriBarangSQL($kd_kategori);
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