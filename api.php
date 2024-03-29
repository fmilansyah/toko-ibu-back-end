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
    $nama = isset($_GET['nama']) ? $_GET['nama'] : null;
    $kd_kategori = isset($_GET['kd_kategori']) ? $_GET['kd_kategori'] : null;
    $barang = new Barang();
    echo $barang->getDataBarangSQL($nama, $kd_kategori);
}

function getBarangTerbaru(){
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $barang = new Barang();
    echo $barang->getBarangTerbaru($search);
}

function getDataBarangPerKategori(){
    if (isset($_GET['kd_kategori'])) {
        
        $kd_kategori = htmlspecialchars($_GET['kd_kategori']);

        $barang = new Barang();
        echo $barang->getBarangPerKategoriSQL($kd_kategori);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getKategoriDanBarang(){
    $barang = new Barang();
    echo $barang->getKategoriDanBarangSQL();
}

function getKategori(){
    $kategori = new Kategori();
    $nama = isset($_GET['nama']) ? $_GET['nama'] : null;
    echo $kategori->getKategoriSQL(false, $nama);
}

function setKategoriBarang(){
    if (isset($_POST['kategori_barang'], $_POST['hapus_kategori_barang'])) {
        
        $kategori_barang = json_decode($_POST['kategori_barang'], true);
        $hapus_kategori_barang = json_decode($_POST['hapus_kategori_barang'], true);

        $kategori = new Kategori();
        echo $kategori->setKategoriBarangSQL($kategori_barang, $hapus_kategori_barang);
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
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $ukuran = json_decode($_POST['ukuran'], true);
        
        $listFile = false;
        if(isset($_FILES['images'])){
            $listFile = $_FILES['images'];
        }

        $barang = new Barang();
        echo $barang->tambahDataBarangSQL($nama, $kd_kategori, $ukuran, $listFile, $deskripsi);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getListOrder(){
    if (isset($_POST['status_order'])) {
        $status_order = htmlspecialchars($_POST['status_order']);
        $startDate = isset($_POST['start_date']) && !empty($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d');
        $endDate = isset($_POST['end_date']) && !empty($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('Y-m-d');

        $order = new Order();
        echo $order->getListOrderSQL($status_order, $startDate, $endDate);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
    
}

function getUserOrder(){
    if (isset($_POST['kd_user'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);

        $order = new Order();
        echo $order->getUserOrderSQL($kd_user);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
    
}

function reportOrder(){
    $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : date('Y-m-d');
    $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : date('Y-m-d');

    $order = new Order();
    echo $order->getReportOrderSQL($startDate, $endDate);
}

function getDetailOrder(){
    if (isset($_POST['kd_order'])) {
        $kd_order = htmlspecialchars($_POST['kd_order']);

        $order = new Order();
        echo $order->getDetailOrderSQL($kd_order);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
    
}

function orderBarang(){
    if (isset($_POST['kd_user'], $_POST['o'], $_POST['jenis_order'], $_POST['jasa_pengiriman'], $_POST['jenis_pengiriman'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);
        $jenis_order = htmlspecialchars($_POST['jenis_order']);
        // $orders = (array) json_decode($_POST['orders'], true);
        $o = json_decode($_POST['o'], true);
        $ongkir = htmlspecialchars($_POST['ongkir']);

        $jasa_pengiriman = htmlspecialchars($_POST['jasa_pengiriman']);
        $kode_jasa_pengiriman = htmlspecialchars($_POST['kode_jasa_pengiriman']);
        $jenis_pengiriman = htmlspecialchars($_POST['jenis_pengiriman']);

        $midtrans_token = isset($_POST['midtrans_token']) ? htmlspecialchars($_POST['midtrans_token']) : null;

        $order = new Order();
        echo $order->orderBarangSQL($kd_user, $jenis_order, $o, $jasa_pengiriman, $jenis_pengiriman, $midtrans_token, $ongkir, $kode_jasa_pengiriman);
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

        $order = new Order();
        echo $order->kirimBarangSQL($kd_order, $no_resi);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function selesaiOrder(){
    if (isset($_POST['kd_order'])) {
        $kd_order = htmlspecialchars($_POST['kd_order']);

        $order = new Order();
        echo $order->selesaiOrderSQL($kd_order);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function updateStatusOrder(){
    if (isset($_POST['kd_order'], $_POST['status_order'])) {
        $kd_order = htmlspecialchars($_POST['kd_order']);
        $status_order = htmlspecialchars($_POST['status_order']);

        $order = new Order();
        echo $order->updateStatusOrderSQL($kd_order, $status_order);
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
        $biteship_area_id = htmlspecialchars($_POST['biteship_area_id']);

        $foto_profil = false;
        if(isset($_FILES['foto_profil'])){
            $foto_profil = $_FILES['foto_profil'];
        }

        $user = new User();
        echo $user->ubahUserSQL($kd_user, $nama, $no_telepon, $email, $alamat, $kode_pos, $foto_profil, $biteship_area_id);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function ubahStatusUser(){
    if (isset($_POST['kd_user'], $_POST['record_status'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);
        $record_status = htmlspecialchars($_POST['record_status']);

        $user = new User();
        echo $user->ubahStatusUserSQL($kd_user, $record_status);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function listUser(){
    $level = isset($_GET['level']) ? $_GET['level'] : null;
    $nama = isset($_GET['nama']) ? $_GET['nama'] : null;
    $user = new User();
    echo $user->getListUser($level, $nama);
}

function daftarUser(){
    if (isset($_POST['nama'], $_POST['no_telepon'], $_POST['password'])) {
        $nama = htmlspecialchars($_POST['nama']);
        $no_telepon = htmlspecialchars($_POST['no_telepon']);
        $password = md5(htmlspecialchars($_POST['password']));
        $level = isset($_POST['level']) ? htmlspecialchars($_POST['level']) : null;

        $user = new User();
        echo $user->daftarUserSQL($nama, $no_telepon, $password, $level);
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
        // $harga_barang = isset($_POST['harga_barang']) ? htmlspecialchars($_POST['harga_barang']) : 0;

        $keranjang = new Keranjang();
        echo $keranjang->tambahKeranjangSQL($kd_user, $kd_detail_barang, $jumlah_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getDataKeranjang(){
    if (isset($_POST['kd_user'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);

        $keranjang = new Keranjang();
        echo $keranjang->getDataKeranjangSQL($kd_user);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function hapusKeranjang(){
    if (isset($_POST['kd_user'], $_POST['kd_user'])) {
        $kd_user = htmlspecialchars($_POST['kd_user']);
        $kd_detail_barang = htmlspecialchars($_POST['kd_detail_barang']);

        $keranjang = new Keranjang();
        echo $keranjang->hapusKeranjangSQL($kd_user, $kd_detail_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

// function hapusKeranjang(){
//     if (isset($_POST['kd_keranjang'])) {
//         $kd_keranjang = htmlspecialchars($_POST['kd_keranjang']);

//         $keranjang = new Keranjang();
//         echo $keranjang->hapusKeranjangSQL($kd_keranjang);
//     } else {
//         $response["Error"] = 1;
//         $response["Message"] = "1102|required field is missing";
//         echo json_encode($response);
//     }
// }

function login(){
    if (isset($_POST['no_telepon'], $_POST['password'])) {
        $no_telepon = htmlspecialchars($_POST['no_telepon']);
        $password = md5(htmlspecialchars($_POST['password']));

        $user = new User();
        echo $user->loginSQL($no_telepon, $password);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function detailUser(){
    if (isset($_GET['kd_user'])) {
        $kd_user = htmlspecialchars($_GET['kd_user']);

        $user = new User();
        echo $user->detailUser($kd_user);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function changeUserPassword(){
    if (isset($_POST['kd_user'], $_POST['old_password'], $_POST['new_password'])) {
        $kdUser = htmlspecialchars($_POST['kd_user']);
        $oldPassword = md5(htmlspecialchars($_POST['old_password']));
        $newPassword = md5(htmlspecialchars($_POST['new_password']));

        $user = new User();
        echo $user->changePassword($kdUser, $oldPassword, $newPassword);
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
        $foto = false;
        if(isset($_FILES['foto'])){
            $foto = $_FILES['foto'];
        }
        
        $kategori = new Kategori();
        echo $kategori->tambahKategoriSQL($nama, $keterangan, $foto);
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
        $deskripsi = htmlspecialchars($_POST['deskripsi']);
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        $ukuran = json_decode($_POST['ukuran'], true);
        $hapus_ukuran = json_decode($_POST['hapus_ukuran'], true);
        $hapus_file = json_decode($_POST['hapus_file'], true);

        $listFile = false;
        if(isset($_FILES['images'])){
            $listFile = $_FILES['images'];
        }

        $barang = new Barang();
        echo $barang->ubahDataBarangSQL($kd_barang, $nama, $kd_kategori, $ukuran, $hapus_ukuran, $hapus_file, $listFile, $deskripsi);
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
        $foto = false;
        if(isset($_FILES['foto'])){
            $foto = $_FILES['foto'];
        }
        
        $kategori = new Kategori();
        echo $kategori->ubahKategoriSQL($kd_kategori, $nama, $keterangan, $foto);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function deleteDataBarang(){
    if (isset($_POST['kd_barang'])) {
        $kd_barang = htmlspecialchars($_POST['kd_barang']);
        
        $barang = new Barang();
        echo $barang->deleteDataBarangSQL($kd_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getDetailBarang(){
    if (isset($_POST['kd_barang'])) {
        $kd_barang = htmlspecialchars($_POST['kd_barang']);
        $barang = new Barang();
        echo $barang->getDetailBarangSQL($kd_barang);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function getKategoriBarang(){
    if (isset($_POST['kd_kategori'])) {
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        $kategori = new Kategori();
        echo $kategori->getKategoriBarangSQL($kd_kategori);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function hapusKategori(){
    if (isset($_POST['kd_kategori'])) {
        $kd_kategori = htmlspecialchars($_POST['kd_kategori']);
        
        $kategori = new Kategori();
        echo $kategori->hapusKategoriSQL($kd_kategori);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function midtransCreateToken(){
    if (isset($_POST['total']) && isset($_POST['kd_order'])) {
        $total = htmlspecialchars($_POST['total']);
        $kdOrder = htmlspecialchars($_POST['kd_order']);

        $midtrans = new Midtrans();
        echo $midtrans->createToken($kdOrder, $total);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function biteshipMaps() {
    $input = isset($_GET['input']) ? htmlspecialchars($_GET['input']) : '';

    $biteship = new Biteship();
    echo $biteship->getMaps($input);
}

function biteshipCouriers() {
    $biteship = new Biteship();
    echo $biteship->getCouriers();
}

function biteshipRates() {
    if (isset($_POST['destination_area_id'], $_POST['couriers'], $_POST['items'])) {
        $destinationAreaId = isset($_POST['destination_area_id']) ? htmlspecialchars($_POST['destination_area_id']) : '';
        $couriers = isset($_POST['couriers']) ? htmlspecialchars($_POST['couriers']) : '';
        $items = isset($_POST['items']) ? json_decode($_POST['items']) : [];

        $biteship = new Biteship();
        echo $biteship->getRates($destinationAreaId, $couriers, $items);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function biteshipTracking() {
    if (isset($_GET['waybill_id'], $_GET['courier'])) {
        $waybillId = htmlspecialchars($_GET['waybill_id']);
        $courier = htmlspecialchars($_GET['courier']);
    
        $biteship = new Biteship();
        echo $biteship->getTracking($waybillId, $courier);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function midtransPaymentSuccess() {
    if (isset(Flight::request()->data->transaction_status) && Flight::request()->data->transaction_status === 'settlement') {
        $orderId = htmlspecialchars(Flight::request()->data->order_id);

        $order = new Order();
        
        $orderId = substr($orderId, 0, -8);
        echo $order->updateStatusOrderSQL($orderId, Order::STATUS_ORDER_WAITING_FOR_CONFIRMATION);
    }
}

function requestResetPassword() {
    if (isset($_POST['email'])) {
        $email = htmlspecialchars($_POST['email']);

        $user = new User();
        echo $user->requestResetPassword($email);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}

function resetPassword() {
    if (isset($_POST['token'], $_POST['password'])) {
        $token = $_POST['token'];
        $password = htmlspecialchars($_POST['password']);

        $user = new User();
        echo $user->resetPassword($token, $password);
    } else {
        $response["Error"] = 1;
        $response["Message"] = "1102|required field is missing";
        echo json_encode($response);
    }
}
?>