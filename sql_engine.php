<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: HTTP_X_API_KEY, HTTP_X_CLIENT_ID, X-api-key, X-client-key, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: HTTP_X_API_KEY, HTTP_X_CLIENT_ID, X-api-key, X-client-key, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

// ini_set( 'default_charset', 'UTF-8' );
// ini_set('memory_limit', '-1');
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/core/core.php';
require_once __DIR__ . '/core/MidtransApi.php';
require_once __DIR__ . '/core/BiteshipApi.php';
require_once __DIR__ . '/helpers/Encryption.php';

const STATUS_ACTIVE = 'A';

$API_URL = '';

$serverName = $_SERVER['SERVER_NAME'];
if ($serverName == 'localhost') {
    // local
    $API_URL = "http://localhost". DIR_API_LOCAL;
    // $globalVar = $GLOBALS[$serverName];
} else {
    // production
    $API_URL = "https://".$serverName. DIR_API_PRO;
    // $globalVar = $GLOBALS[$delSlash]; 
}

class Barang{
    public function getDetailBarangSQL($kd_barang){
        $barang = "SELECT * FROM `barang` WHERE kd_barang=:kd_barang";
        $result_barang = coreReturnArray($barang, array(":kd_barang" => $kd_barang));
    
        $detail_barang = "SELECT * FROM `detail_barang` WHERE kd_barang=:kd_barang ORDER BY created_at ASC";
        $result_detail_barang = coreReturnArray($detail_barang, array(":kd_barang" => $kd_barang));
    
        $file_barang = "SELECT * FROM `file_barang` WHERE kd_barang=:kd_barang ORDER BY created_at ASC";
        $result_file_barang = coreReturnArray($file_barang, array(":kd_barang" => $kd_barang));
    
        $kategori_barang = "SELECT k.* FROM `kategori` k INNER JOIN barang b ON k.kd_kategori=b.kd_kategori WHERE b.kd_barang=:kd_barang";
        $result_kategori_barang = coreReturnArray($kategori_barang, array(":kd_barang" => $kd_barang));
    
        if (sizeof($result_barang) > 0 || sizeof($result_detail_barang) > 0 || sizeof($result_file_barang) > 0) {
            $response['Error'] = 0;
    
            if (sizeof($result_barang) > 0) {
                $response['barang'] = $result_barang[0];
            }
            if (sizeof($result_detail_barang) > 0) {
                $response['detail_barang'] = $result_detail_barang;
            }
            if (sizeof($result_file_barang) > 0) {
                $response['file_barang'] = $result_file_barang;
            }
            if (sizeof($result_kategori_barang) > 0) {
                $response['kategori_barang'] = $result_kategori_barang[0];
            }
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }
        else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function getDataBarangSQL($nama){
        $sql = "SELECT b.*, k.nama AS nama_kategori, (SELECT COUNT(*) FROM detail_barang db WHERE db.kd_barang = b.kd_barang) AS jumlah_varian FROM barang b INNER JOIN kategori k ON b.kd_kategori=k.kd_kategori WHERE b.record_status=:record_status ";
        if ($nama) {
            $sql .= " AND b.nama LIKE '%".$nama."%' ";
        }
        $sql .= "ORDER BY created_at DESC";
        $result = coreReturnArray($sql, [':record_status' => STATUS_ACTIVE]);
    
        if (sizeof($result) > 0) {
            foreach ($result as $key => $item) {
                $getFiles = 'SELECT * FROM file_barang WHERE kd_barang=:kd_barang ORDER BY created_at ASC LIMIT 1';
                $files = coreReturnArray($getFiles, array(":kd_barang" => $item['kd_barang']));
                if (sizeof($files) > 0) {
                    $result[$key]['file'] = $files[0]['file'];
                }
            }
            $response['Error'] = 0;
            $response['Barang'] = $result;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function getKategoriDanBarangSQL(){
        $kategori = new Kategori();
        $categories = $kategori->getKategoriSQL(true);
    
        $result = [];
        foreach ($categories as $category) {
            $sql = 'SELECT * FROM barang WHERE kd_kategori=:kd_kategori AND record_status=:record_status LIMIT 10';
            $items = coreReturnArray($sql, array(":kd_kategori" => $category['kd_kategori'], ":record_status"=>STATUS_ACTIVE));

            foreach ($items as $key => $item) {
                $getVariant = 'SELECT * FROM detail_barang WHERE kd_barang=:kd_barang ORDER BY harga ASC LIMIT 1';
                $variant = coreReturnArray($getVariant, array(":kd_barang" => $item['kd_barang']));
                if (sizeof($variant) > 0) {
                    $items[$key]['kd_detail_barang'] = $variant[0]['kd_detail_barang'];
                    $items[$key]['varian'] = $variant[0]['varian'];
                    $items[$key]['harga'] = $variant[0]['harga'];
                }
    
                $getFiles = 'SELECT * FROM file_barang WHERE kd_barang=:kd_barang ORDER BY created_at ASC LIMIT 1';
                $files = coreReturnArray($getFiles, array(":kd_barang" => $item['kd_barang']));
                if (sizeof($files) > 0) {
                    $items[$key]['file'] = $files[0]['file'];
                }
            }
    
            if (sizeof($items) > 0) {
                $result[] = [
                    'kd_kategori' => $category['kd_kategori'],
                    'nama' => $category['nama'],
                    'jml_barang' => sizeof($items),
                    'barang' => $items,
                ];
            }
        }
    
        if (sizeof($result) > 0) {
            $response['Error'] = 0;
            $response['Kategori'] = $result;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function getBarangPerKategoriSQL($kd_kategori){
        $sql = 'SELECT * FROM barang WHERE kd_kategori=:kd_kategori AND record_status=:record_status';
        $items = coreReturnArray($sql, array(":kd_kategori" => $kd_kategori, ":record_status"=>STATUS_ACTIVE));
    
        foreach ($items as $key => $item) {
            $getVariant = 'SELECT * FROM detail_barang WHERE kd_barang=:kd_barang ORDER BY harga ASC LIMIT 1';
            $variant = coreReturnArray($getVariant, array(":kd_barang" => $item['kd_barang']));
            if (sizeof($variant) > 0) {
                $items[$key]['kd_detail_barang'] = $variant[0]['kd_detail_barang'];
                $items[$key]['varian'] = $variant[0]['varian'];
                $items[$key]['harga'] = $variant[0]['harga'];
            }
    
            $getFiles = 'SELECT * FROM file_barang WHERE kd_barang=:kd_barang ORDER BY created_at ASC LIMIT 1';
            $files = coreReturnArray($getFiles, array(":kd_barang" => $item['kd_barang']));
            if (sizeof($files) > 0) {
                $items[$key]['file'] = $files[0]['file'];
            }
        }
    
        if (sizeof($items) > 0) {
            $response['Error'] = 0;
            $response['Barang'] = $items;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function getBarangTerbaru($search = null){
        $sql = 'SELECT * FROM barang WHERE record_status=:record_status ';
        if ($search) {
            $sql .= "AND nama LIKE '%".$search."%'";
        }
        $sql .= 'ORDER BY created_at DESC LIMIT 10';
        $items = coreReturnArray($sql, array(":record_status"=>STATUS_ACTIVE));
    
        foreach ($items as $key => $item) {
            $getVariant = 'SELECT * FROM detail_barang WHERE kd_barang=:kd_barang ORDER BY harga ASC LIMIT 1';
            $variant = coreReturnArray($getVariant, array(":kd_barang" => $item['kd_barang']));
            if (sizeof($variant) > 0) {
                $items[$key]['kd_detail_barang'] = $variant[0]['kd_detail_barang'];
                $items[$key]['varian'] = $variant[0]['varian'];
                $items[$key]['harga'] = $variant[0]['harga'];
            }
    
            $getFiles = 'SELECT * FROM file_barang WHERE kd_barang=:kd_barang ORDER BY created_at ASC LIMIT 1';
            $files = coreReturnArray($getFiles, array(":kd_barang" => $item['kd_barang']));
            if (sizeof($files) > 0) {
                $items[$key]['file'] = $files[0]['file'];
            }
        }
    
        if (sizeof($items) > 0) {
            $response['Error'] = 0;
            $response['Barang'] = $items;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function tambahDataBarangSQL($nama, $kd_kategori, $ukuran, $listFile, $deskripsi){
        $getLastId = json_decode(getLastIdTable('kd_barang', 'barang'), true);
        $lastId = $getLastId['data'];
        $kd_barang = 'B'.$lastId;
        
        $sql = "INSERT INTO barang(kd_barang, nama, kd_kategori, deskripsi) VALUES(:kd_barang, :nama, :kd_kategori, :deskripsi)";
        $result = coreNoReturn($sql, array(":kd_barang" => $kd_barang, ":nama" => $nama, ":kd_kategori" => $kd_kategori, ":deskripsi" => $deskripsi));
            
        if ($result['success'] == 1) {
            
            $jumlah_ukuran = sizeof($ukuran);
            if($jumlah_ukuran > 0){
                $hitung_input_ukuran = 0;
                foreach ($ukuran as $key => $data) {
                    $getLastId = json_decode(getLastIdTable('kd_detail_barang', 'detail_barang'), true);
                    $lastId = $getLastId['data'];
                    $kd_detail_barang = 'DB'.$lastId;
    
                    $sql_i_Ukuran = "INSERT INTO `detail_barang`(`kd_detail_barang`, `kd_barang`, `varian`, `stok`, `harga`, `berat_satuan`) 
                            VALUES (:kd_detail_barang, :kd_barang, :varian, :stok, :harga, :berat_satuan)";
                    $result_i_ukuran = coreNoReturn($sql_i_Ukuran, array(
                                                        ":kd_detail_barang" => $kd_detail_barang, 
                                                        ":kd_barang" => $kd_barang, 
                                                        ":varian" => $data['varian'],
                                                        ":stok" => $data['stok'],
                                                        ":harga" => $data['harga'],
                                                        ":berat_satuan" => $data['berat_satuan']
                    ));
                    if ($result_i_ukuran['success'] == 1) {
                        $hitung_input_ukuran++;
                        sleep(1);
                    }
                }
                if($jumlah_ukuran == $hitung_input_ukuran){
                    $response['Message_Ukuran'] = "Berhasil Menambahkan Ukuran Barang!";
                }else{
                    $response['Message_Ukuran'] = "Gagal Menambahkan Ukuran Barang!";
                }
            }
            
            if ($listFile != false) {
                $sql = "INSERT INTO file_barang(kd_file, kd_barang, file) 
                    VALUES(:kd_file, :kd_barang, :file)";
                    
                $jumlah_i_file = sizeof($listFile['name']);
                $hitung_upload = 0;
                foreach($listFile['name'] as $keyFile => $in){
    
                    $getLastId = json_decode(getLastIdTable('kd_file', 'file_barang'), true);
                    $lastId = $getLastId['data'];
                    $kd_file = 'FB'.$lastId;

                    $tipe = $listFile['type'][$keyFile];
                    $fileName = date("YmdHis"). "-" .$in; 
                    $fileName = hilangSimbol($fileName);
    
                    $fileDir = $GLOBALS['API_URL'].'/assets/file/'.date("Y/m/d");
    
                    $result = coreNoReturn($sql, array(":kd_file" => $kd_file, ":kd_barang" => $kd_barang, ":file" => $fileDir .'/'. urldecode($fileName)));
    
                    if (!file_exists('assets/file/'.date("Y/m/d").'/')) {
                        mkdir('assets/file/'.date("Y/m/d").'/', 0777, true);
                    }    
                    if ($result['success'] == 1 ) {
                        if($tipe =='application/pdf' || $tipe == 'video/mp4' || $tipe == 'video/3gpp' ||
                            $tipe == 'video/x-matroska' || $tipe == 'video/avi' || $tipe == 'video/webm' || $tipe == 'audio/mpeg'){
                            move_uploaded_file($listFile['tmp_name'][$keyFile], 'assets/file/'.date("Y/m/d").'/'.urldecode($fileName));
                        }else{
                            compress_image($listFile['tmp_name'][$keyFile], __DIR__.'/assets/file/'.date("Y/m/d").'/'.urldecode($fileName), 50);
                        }
                        
                        $hitung_upload++;
                        sleep(1);
                    }
                }
                if($jumlah_i_file == $hitung_upload){
                    $response['Message_Upload'] = "Berhasil Mengupload File!";
                } else {
                    $response['Message_Upload'] = "Gagal Mengupload File!";
                }
            }
                
            // $response['fileDir'] = $fileDir;
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Menambahkan Barang!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Menambahkan Barang!";
            return json_encode($response);
        }
    }

    public function ubahDataBarangSQL($kd_barang, $nama, $kd_kategori, $ukuran, $hapus_ukuran, $hapus_file, $listFile, $deskripsi){
    
        $sql = "UPDATE `barang` SET `nama`=:nama, `kd_kategori`=:kd_kategori, `deskripsi`=:deskripsi WHERE `kd_barang`=:kd_barang";
        $result = coreNoReturn($sql, array(":kd_barang"=>$kd_barang, ":nama"=>$nama, ":kd_kategori"=>$kd_kategori, ":deskripsi"=>$deskripsi));
            
        if ($result['success'] == 1) {
    
            foreach ($hapus_ukuran as $key => $value) {
                $sql_delete_ukuran = "DELETE FROM detail_barang WHERE `kd_detail_barang`=:kd_detail_barang";
                $result_delete_ukuran = coreNoReturn($sql_delete_ukuran, array(":kd_detail_barang"=>$value));
            }
    
            foreach ($hapus_file as $key => $value) {
                $sql_delete_file = "DELETE FROM file_barang WHERE `kd_file`=:kd_file";
                $result_delete_file = coreNoReturn($sql_delete_file, array(":kd_file"=>$value));
            }
            
            function getLastIdDB(){
                $getLastId = json_decode(getLastIdTable('kd_detail_barang', 'detail_barang'), true);
                $lastId = $getLastId['data'];
                $kd_DB = 'DB'.$lastId;
                return $kd_DB;
            }
    
            $jumlah_ukuran = sizeof($ukuran);
            if($jumlah_ukuran > 0){
                $hitung_input_ukuran = 0;
                foreach ($ukuran as $key => $data) {
                    $sql_i_Ukuran = "INSERT INTO `detail_barang`(`kd_detail_barang`, `kd_barang`, `varian`, `stok`, `harga`, `berat_satuan`) 
                            VALUES (:kd_detail_barang, :kd_barang, :varian, :stok, :harga, :berat_satuan)
                            ON DUPLICATE KEY 
                            UPDATE `varian`= :varian2, `stok`= :stok2, `harga`= :harga2, `berat_satuan`= :berat_satuan2 " ;
                    $result_i_ukuran = coreNoReturn($sql_i_Ukuran, array(
                                                        ":kd_detail_barang" => isset($data['kd_detail_barang']) ? $data['kd_detail_barang'] : getLastIdDB(), 
                                                        ":kd_barang" => $kd_barang, 
                                                        ":varian" => $data['varian'],
                                                        ":varian2" => $data['varian'],
                                                        ":stok" => $data['stok'],
                                                        ":stok2" => $data['stok'],
                                                        ":harga" => $data['harga'],
                                                        ":harga2" => $data['harga'],
                                                        ":berat_satuan" => $data['berat_satuan'],
                                                        ":berat_satuan2" => $data['berat_satuan']
                    ));
                    if ($result_i_ukuran['success'] == 1) {
                        $hitung_input_ukuran++;
                    }
                }
                if($jumlah_ukuran == $hitung_input_ukuran){
                    $response['Message_Ukuran'] = "Berhasil Menyimpan Varian Barang!";
                }else{
                    $response['Message_Ukuran'] = "Gagal Menyimpan Varian Barang!";
                }
            }
    
            if ($listFile != false) {
                $sql = "INSERT INTO file_barang(kd_file, kd_barang, file) 
                    VALUES(:kd_file, :kd_barang, :file)";
                    
                $jumlah_i_file = sizeof($listFile['name']);
                $hitung_upload = 0;
                foreach($listFile['name'] as $keyFile => $in){
                    
                    $tipe = $listFile['type'][$keyFile];
                    $fileName = date("YmdHis"). "-" .$in; 
                    $fileName = hilangSimbol($fileName);
    
                    $fileDir = $GLOBALS['API_URL'].'/assets/file/'.date("Y/m/d").'/';
                    
                    $getLastId = json_decode(getLastIdTable('kd_file', 'file_barang'), true);
                    $lastId = $getLastId['data'];
                    $kd_file = 'FB'.$lastId;
    
                    $result = coreNoReturn($sql, array(":kd_file" => $kd_file, ":kd_barang" => $kd_barang, ":file" => $fileDir .'/'. urldecode($fileName)));
    
                    if (!file_exists('assets/file/'.date("Y/m/d").'/')) {
                        mkdir('assets/file/'.date("Y/m/d").'/', 0777, true);
                    }    
    
                    if ($result['success'] == 1 ) {
                        if($tipe =='application/pdf' || $tipe == 'video/mp4' || $tipe == 'video/3gpp' ||
                            $tipe == 'video/x-matroska' || $tipe == 'video/avi' || $tipe == 'video/webm' || $tipe == 'audio/mpeg'){
                            move_uploaded_file($listFile['tmp_name'][$keyFile], 'assets/file/'.date("Y/m/d").'/'.urldecode($fileName));
                        }else{
                            compress_image($listFile['tmp_name'][$keyFile], __DIR__.'/assets/file/'.date("Y/m/d").'/'.urldecode($fileName), 50);
                        }
                        
                        $hitung_upload++;
                    }
                }
                if($jumlah_i_file == $hitung_upload){
                    $response['Message_Upload'] = "Berhasil Mengupload File!";
                } else {
                    $response['Message_Upload'] = "Gagal Mengupload File!";
                }
            }
    
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mengubah Data!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mengubah Data!";
            return json_encode($response);
        }
    }

    public function deleteDataBarangSQL($kd_barang){
    
        $sql = "UPDATE `barang` SET `record_status`=:record_status WHERE `kd_barang`=:kd_barang";
        $result = coreNoReturn($sql, array(":kd_barang"=>$kd_barang, ":record_status"=>'D'));
            
        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Menghapus Data!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Menghapus Data!";
            return json_encode($response);
        }
    }
}

class Keranjang {
    public function getDataKeranjangSQL($kd_user){
        $sql = "SELECT k.*, b.kd_barang, b.nama, db.varian, db.berat_satuan, (db.harga * k.jumlah_barang) as harga_total FROM keranjang k INNER JOIN detail_barang db ON k.kd_detail_barang=db.kd_detail_barang AND k.kd_user=:kd_user INNER JOIN barang b ON db.kd_barang=b.kd_barang ORDER BY k.created_at DESC";
        $result = coreReturnArray($sql, array(":kd_user" => $kd_user));

        foreach ($result as $key => $val) {
            $file_barang = "SELECT * FROM `file_barang` WHERE kd_barang=:kd_barang ORDER BY created_at ASC";
            $result_file_barang = coreReturnArray($file_barang, array(":kd_barang" => $val['kd_barang']));
    
            if (sizeof($result_file_barang) > 0) {
                $result[$key]['file'] = $result_file_barang[0]['file'];
            }
        }
    
        if (sizeof($result) > 0) {
            $response['Error'] = 0;
            $response['Keranjang'] = $result;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }
        else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }
    
    public function hapusKeranjangSQL($kd_user, $kd_detail_barang){
        $sql = "SELECT * FROM keranjang WHERE kd_user=:kd_user AND kd_detail_barang=:kd_detail_barang";
        $result = coreReturnArray($sql, array(":kd_user" => $kd_user, ":kd_detail_barang" => $kd_detail_barang));
        
        if (sizeof($result) > 0) {
            $kd_detail_barang = $result[0]['kd_detail_barang'];
            $jumlah_barang = $result[0]['jumlah_barang'];

            $sqlUpdateStok = "UPDATE `detail_barang` db SET `stok`=db.stok+".intval($jumlah_barang)." WHERE `kd_detail_barang`=:kd_detail_barang";
            $resultUpdateStok = coreNoReturn($sqlUpdateStok, array(":kd_detail_barang"=>$kd_detail_barang));
            if ($resultUpdateStok['success'] == 1) {
                $response['MessageUpdateStok'] = "Berhasil Mengembalikan Stok Barang!";

                $sql_delete_keranjang = "DELETE FROM keranjang WHERE kd_user=:kd_user AND kd_detail_barang=:kd_detail_barang";
                $result_delete_keranjang = coreNoReturn($sql_delete_keranjang, array(":kd_user" => $kd_user, ":kd_detail_barang" => $kd_detail_barang));
                if ($result_delete_keranjang['success'] == 1) {
                    $response['Error'] = 0;
                    $response['Message'] = "Berhasil Menghapus Barang Dari Keranjang!";
                    return json_encode($response);
                }else {
                    $response['Error'] = 1;
                    $response['Message'] = "Gagal Menghapus Barang Dari Keranjang!";
                    return json_encode($response);
                }
            }
        }else{
            $response['Error'] = 1;
            $response['Message'] = "Barang Tidak Ditemukan Dalam Keranjang!";
            return json_encode($response);
        }
    }

    // public function hapusKeranjangSQL($kd_keranjang){
    //     $sql = "SELECT * FROM keranjang WHERE kd_keranjang=:kd_keranjang";
    //     $result = coreReturnArray($sql, array(":kd_keranjang" => $kd_keranjang));
        
    //     if (sizeof($result) > 0) {
    //         $kd_detail_barang = $result[0]['kd_detail_barang'];
    //         $jumlah_barang = $result[0]['jumlah_barang'];

    //         $sqlUpdateStok = "UPDATE `detail_barang` db SET `stok`=db.stok+".intval($jumlah_barang)." WHERE `kd_detail_barang`=:kd_detail_barang";
    //         $resultUpdateStok = coreNoReturn($sqlUpdateStok, array(":kd_detail_barang"=>$kd_detail_barang));
    //         if ($resultUpdateStok['success'] == 1) {
    //             $response['MessageUpdateStok'] = "Berhasil Mengembalikan Stok Barang!";

    //             $sql_delete_keranjang = "DELETE FROM keranjang WHERE `kd_keranjang`=:kd_keranjang";
    //             $result_delete_keranjang = coreNoReturn($sql_delete_keranjang, array(":kd_keranjang"=>$kd_keranjang));
    //             if ($result_delete_keranjang['success'] == 1) {
    //                 $response['Error'] = 0;
    //                 $response['Message'] = "Berhasil Menghapus Barang Dari Keranjang!";
    //                 return json_encode($response);
    //             }else {
    //                 $response['Error'] = 1;
    //                 $response['Message'] = "Gagal Menghapus Barang Dari Keranjang!";
    //                 return json_encode($response);
    //             }
    //         }
    //     }else{
    //         $response['Error'] = 1;
    //         $response['Message'] = "Barang Tidak Ditemukan Dalam Keranjang!";
    //         return json_encode($response);
    //     }
    // }

    public function hapusKeranjangOrder($kd_detail_barang, $kd_user){
        $sql_delete_keranjang = "DELETE FROM keranjang WHERE `kd_user`=:kd_user AND kd_detail_barang=:kd_detail_barang";
        $result_delete_keranjang = coreNoReturn($sql_delete_keranjang, array(":kd_user"=>$kd_user, ":kd_detail_barang"=>$kd_detail_barang));
        
        if ($result_delete_keranjang['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Menghapus Barang Dari Keranjang!";
            return json_encode($response);
        }else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Menghapus Barang Dari Keranjang!";
            return json_encode($response);
        }
        
    }

    public function tambahKeranjangSQL($kd_user, $kd_detail_barang, $jumlah_barang){

        $sql = "SELECT * FROM keranjang WHERE kd_user=:kd_user AND kd_detail_barang=:kd_detail_barang";
        $result = coreReturnArray($sql, array(":kd_user" => $kd_user, ":kd_detail_barang" => $kd_detail_barang));

        if (sizeof($result) > 0) {
            $sql = "UPDATE `keranjang` db SET `jumlah_barang`=db.jumlah_barang+".intval($jumlah_barang)." WHERE `kd_detail_barang`=:kd_detail_barang AND kd_user=:kd_user";
            $result = coreNoReturn($sql, array(":kd_detail_barang"=>$kd_detail_barang, ":kd_user"=>$kd_user));
        }else{
            $sql = "INSERT INTO keranjang(kd_user, kd_detail_barang, jumlah_barang) VALUES(:kd_user, :kd_detail_barang, :jumlah_barang)";
            $result = coreNoReturn($sql, array(":kd_detail_barang" => $kd_detail_barang, ":kd_user" => $kd_user, ":jumlah_barang" => $jumlah_barang));    
        }
        
        if ($result['success'] == 1) {

            $sqlUpdateStok = "UPDATE `detail_barang` db SET `stok`=db.stok-".intval($jumlah_barang)." WHERE `kd_detail_barang`=:kd_detail_barang";
            $resultUpdateStok = coreNoReturn($sqlUpdateStok, array(":kd_detail_barang"=>$kd_detail_barang));
            if ($resultUpdateStok['success'] == 1) {
                $response['MessageUpdateStok'] = "Berhasil Mengurangi Stok Barang!";
            }
            
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Menambahkan Keranjang!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Menambahkan Keranjang!";
            return json_encode($response);
        }
    }

    // public function tambahKeranjangSQL($kd_user, $kd_detail_barang, $jumlah_barang){

    //     $sql = "SELECT * FROM keranjang WHERE kd_user=:kd_user AND kd_detail_barang=:kd_detail_barang";
    //     $result = coreReturnArray($sql, array(":kd_user" => $kd_user, ":kd_detail_barang" => $kd_detail_barang));

    //     if (sizeof($result) > 0) {
    //         $sql = "UPDATE `keranjang` db SET `jumlah_barang`=db.jumlah_barang+".intval($jumlah_barang)." WHERE `kd_detail_barang`=:kd_detail_barang AND kd_user=:kd_user";
    //         $result = coreNoReturn($sql, array(":kd_detail_barang"=>$kd_detail_barang, ":kd_user"=>$kd_user));
    //     }else{
    //         $getLastId = json_decode(getLastIdTable('kd_keranjang', 'keranjang'), true);
    //         $lastId = $getLastId['data'];
    //         $kd_keranjang = 'KER'.$lastId;

    //         $sql = "INSERT INTO keranjang(kd_keranjang, kd_user, kd_detail_barang, jumlah_barang) VALUES(:kd_keranjang, :kd_user, :kd_detail_barang, :jumlah_barang)";
    //         $result = coreNoReturn($sql, array(":kd_keranjang" => $kd_keranjang, ":kd_detail_barang" => $kd_detail_barang, ":kd_user" => $kd_user, ":jumlah_barang" => $jumlah_barang));    
    //     }
        
    //     if ($result['success'] == 1) {

    //         $sqlUpdateStok = "UPDATE `detail_barang` db SET `stok`=db.stok-".intval($jumlah_barang)." WHERE `kd_detail_barang`=:kd_detail_barang";
    //         $resultUpdateStok = coreNoReturn($sqlUpdateStok, array(":kd_detail_barang"=>$kd_detail_barang));
    //         if ($resultUpdateStok['success'] == 1) {
    //             $response['MessageUpdateStok'] = "Berhasil Mengurangi Stok Barang!";
    //         }
            
    //         $response['Error'] = 0;
    //         $response['Message'] = "Berhasil Menambahkan Keranjang!";
    //         return json_encode($response);
    //     } else {
    //         $response['Error'] = 1;
    //         $response['Message'] = "Gagal Menambahkan Keranjang!";
    //         return json_encode($response);
    //     }
    // }
}

class Kategori {
    public function tambahKategoriSQL($nama, $keterangan, $foto){
        $getLastId = json_decode(getLastIdTable('kd_kategori', 'kategori'), true);
        $lastId = $getLastId['data'];
        $kd_kategori = 'KAT'.$lastId;

        if($foto == false){
            $sql = "INSERT INTO kategori(kd_kategori, nama, keterangan) VALUES(:kd_kategori, :nama, :keterangan)";
            $result = coreNoReturn($sql, array(":kd_kategori" => $kd_kategori, ":nama" => $nama, ":keterangan" => $keterangan));
        }else{
            $fp = uploadFileSQL2($foto);
            $sql = "INSERT INTO kategori(kd_kategori, nama, keterangan, foto) VALUES(:kd_kategori, :nama, :keterangan, :foto)";
            $result = coreNoReturn($sql, array(":kd_kategori" => $kd_kategori, ":nama" => $nama, ":keterangan" => $keterangan, ":foto" => $fp));
            // $response['fp'] = $fp;
        }

        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Menambahkan Kategori!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Menambahkan Kategori!";
            return json_encode($response);
        }
    }

    public function ubahKategoriSQL($kd_kategori, $nama, $keterangan, $foto){
        if($foto == false){
            $sql = "UPDATE `kategori` SET `nama`=:nama, `keterangan`=:keterangan  WHERE `kd_kategori`=:kd_kategori";
            $result = coreNoReturn($sql, array(":kd_kategori"=>$kd_kategori, ":nama"=>$nama, ":keterangan"=>$keterangan));
        }else{
            $fp = uploadFileSQL2($foto);
            $sql = "UPDATE `kategori` SET `nama`=:nama, `keterangan`=:keterangan, `foto`=:foto  WHERE `kd_kategori`=:kd_kategori";
            $result = coreNoReturn($sql, array(":kd_kategori"=>$kd_kategori, ":nama"=>$nama, ":keterangan"=>$keterangan,":foto"=>$fp));
            // $response['fp'] = $fp;
        }
    
        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mengubah Kategori!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mengubah Kategori!";
            return json_encode($response);
        }
    }
    
    public function getKategoriSQL($returnData = false, $nama = null){
        $sql = "SELECT k.*, (SELECT COUNT(*) FROM barang b WHERE b.kd_kategori = k.kd_kategori AND b.record_status=:record_status) AS jumlah_produk FROM kategori k ";
        if ($nama) {
            $sql .= "WHERE k.nama LIKE '%".$nama."%' ";
        }
        $sql .= " ORDER BY k.created_at DESC";
        $result = coreReturnArray($sql, [':record_status' => STATUS_ACTIVE]);
    
        if ($returnData) {
            return $result;
        }
    
        if (sizeof($result) > 0) {
            $response['Error'] = 0;
            $response['Kategori'] = $result;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function setKategoriBarangSQL($kategori_barang, $hapus_kategori_barang){

        $JKB = sizeof($kategori_barang);
        if($JKB > 0 ){
            $CJKB = 0;
            foreach ($kategori_barang as $key => $data) {
                $sql = "INSERT INTO `kategori_barang`(`kd_kategori`, `kd_barang`) 
                                VALUES (:kd_kategori, :kd_barang)
                                ON DUPLICATE KEY 
                                UPDATE `kd_kategori`= :kd_kategori2, `kd_barang`= :kd_barang2";
        
                $result = coreNoReturn($sql, array(
                                                ":kd_kategori" => $data['kd_kategori'], 
                                                ":kd_kategori2" => $data['kd_kategori'], 
                                                ":kd_barang" => $data['kd_barang'], 
                                                ":kd_barang2" => $data['kd_barang'], 
                ));
                if ($result['success'] == 1) {
                    $CJKB++;
                }
            }
            if($JKB == $CJKB){
                $response['MessageKategoriBarang'] = 'Data Berhasil Disimpan!';
            }else{
                $response['MessageKategoriBarang'] = 'Data Gagal Disimpan!';
            }
        }
        
    
        $JHKB = sizeof($hapus_kategori_barang);
        if($JHKB > 0){
            $CHJKB = 0;
            foreach ($hapus_kategori_barang as $key => $data2) {
                $sql = "DELETE FROM `kategori_barang` WHERE `kd_kategori`=:kd_kategori AND kd_barang=:kd_barang";
                $result = coreNoReturn($sql, array(":kd_kategori"=>$data2['kd_kategori'], ":kd_barang"=>$data2['kd_barang']));
                if ($result['success'] == 1) {
                    $CHJKB++;
                }
            }
            
            if($JHKB == $CHJKB){
                $response['MessageHapusKategoriBarang'] = 'Data Berhasil Dihapus!';
            }else{
                $response['MessageHapusKategoriBarang'] = 'Data Gagal Dihapus!';
            }
        }
    
        return json_encode($response);
    }
    
    public function getKategoriBarangSQL($kd_kategori){
        $sql = "SELECT b.* FROM barang b 
                    INNER JOIN kategori_barang kb ON b.kd_barang=kb.kd_barang AND kb.kd_kategori=:kd_kategori
                    ORDER BY b.created_at DESC";
        $result = coreReturnArray($sql, array(":kd_kategori" => $kd_kategori));
    
        if (sizeof($result) > 0) {
            $response['Error'] = 0;
            $response['KategoriBarang'] = $result;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }
        else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    function hapusKategoriSQL($kd_kategori){

        $sql = "UPDATE `barang` SET kd_kategori=NULL WHERE `kd_kategori`=:kd_kategori";
        $result = coreNoReturn($sql, array(":kd_kategori"=>$kd_kategori));
    
        if ($result['success'] == 1) {
    
            $sql2 = "DELETE FROM `kategori` WHERE `kd_kategori`=:kd_kategori";
            $result2 = coreNoReturn($sql2, array(":kd_kategori"=>$kd_kategori));
    
            if ($result2['success'] == 1) {
                $response['MessageKategori'] = "Berhasil Menghapus Kategori!";    
            }else{
                $response['MessageKategori'] = "Gagal Menghapus Kategori!";    
            }
    
            $response['Error'] = 0;
            $response['MessageKategoriBarang'] = "Berhasil Menghapus Kategori Barang!";    
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Menghapus Data!";
            return json_encode($response);
        }
    }
}

class Order {
    const STATUS_ORDER_WAITING_FOR_PAYMENT = 'Menunggu Pembayaran';
    const STATUS_ORDER_WAITING_FOR_CONFIRMATION = 'Menunggu Konfirmasi';
    const STATUS_ORDER_PROCESS = 'Diproses';
    const STATUS_ORDER_DELIVERY = 'Dikirim';
    const STATUS_ORDER_FINISHED = 'Selesai';
    const STATUS_ORDER_CANCELLED = 'Batal';

    public function kirimBarangSQL($kd_order, $no_resi){
        $status_order = self::STATUS_ORDER_DELIVERY;
        $sql = "UPDATE `order` db SET `no_resi`=:no_resi, status_order=:status_order WHERE `kd_order`=:kd_order";
        $result = coreNoReturn($sql, array(":no_resi"=>$no_resi, ":status_order"=>$status_order, ":kd_order"=>$kd_order));
    
        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mengubah Status Order!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mengubah Status Order!";
            return json_encode($response);
        }
    }
    
    public function selesaiOrderSQL($kd_order){
        $status_order = self::STATUS_ORDER_PROCESS;
        $sql = "UPDATE `order` db SET status_order=:status_order WHERE `kd_order`=:kd_order";
        $result = coreNoReturn($sql, array(":status_order"=>$status_order, ":kd_order"=>$kd_order));
    
        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mengubah Status Order!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mengubah Status Order!";
            return json_encode($response);
        }
    }

    public function updateStatusOrderSQL($kd_order, $status_order){
        if ($status_order === self::STATUS_ORDER_WAITING_FOR_CONFIRMATION) {
            $sql = "UPDATE `order` db SET status_order=:status_order, status_pembayaran=:status_pembayaran WHERE `kd_order`=:kd_order";
            $result = coreNoReturn($sql, array(":status_order"=>$status_order, ":kd_order"=>$kd_order, ":status_pembayaran" => 'LUNAS'));
        } else {
            $sql = "UPDATE `order` db SET status_order=:status_order WHERE `kd_order`=:kd_order";
            $result = coreNoReturn($sql, array(":status_order"=>$status_order, ":kd_order"=>$kd_order));
        }
    
        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mengubah Status Order!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mengubah Status Order!";
            return json_encode($response);
        }
    }

    public function orderBarangSQL($kd_user, $jenis_order, $orders, $jasa_pengiriman, $jenis_pengiriman, $midtrans_token = null, $ongkir, $kode_jasa_pengiriman){
        $getLastId = json_decode(getLastIdTable('kd_order', 'order'), true);
        $lastId = $getLastId['data'];
        $kd_order = 'O'.$lastId;
        $midtransOrderId = $kd_order . date('his');
    
        $total_akhir = 0;
        foreach ($orders as $key => $value) {
            $total_akhir += $value['harga_total'];
        }

        $snapData = [
            'transaction_details' => [
                'order_id' => $midtransOrderId,
                'gross_amount' => $total_akhir + $ongkir,
            ],
        ];
        $midtrans = new MidtransApi();
        $pay = $midtrans->request(MidtransApi::TYPE_SNAP, 'POST', '/snap/v1/transactions', $snapData);

        $status_pembayaran = 'MENUNGGU PEMBAYARAN';
        $status_order = self::STATUS_ORDER_WAITING_FOR_PAYMENT;
        $sqlOrder = "INSERT INTO `order` (kd_order, kd_user, total_akhir, tanggal_pembayaran, status_pembayaran, jasa_pengiriman, jenis_pengiriman, status_order, midtrans_token, ongkir, kode_jasa_pengiriman, midtrans_order_id) VALUES(:kd_order, :kd_user, :total_akhir, CURRENT_TIMESTAMP, :status_pembayaran, :jasa_pengiriman, :jenis_pengiriman, :status_order, :midtrans_token, :ongkir, :kode_jasa_pengiriman, :midtrans_order_id)";
        $resultOrder = coreNoReturn($sqlOrder, array(":kd_order" => $kd_order, ":kd_user" => $kd_user, ":total_akhir" => $total_akhir, ":status_pembayaran" => $status_pembayaran, ":jasa_pengiriman" => $jasa_pengiriman, ":jenis_pengiriman" => $jenis_pengiriman, ":status_order" => $status_order, ":midtrans_token" => $pay['body']['token'], ":ongkir" => $ongkir, ":kode_jasa_pengiriman" => $kode_jasa_pengiriman, ":midtrans_order_id" => $midtransOrderId));

        if ($resultOrder['success'] == 1) {
    
            foreach ($orders as $key => $value) {
                $sqlOrderDetail = "INSERT INTO detail_order(kd_order, kd_detail_barang, jumlah_barang, total_harga) VALUES(:kd_order, :kd_detail_barang, :jumlah_barang, :total_harga)";
                $resultOrderDetail = coreNoReturn($sqlOrderDetail, array(":kd_order" => $kd_order, ":kd_detail_barang" => $value['kd_detail_barang'], ":jumlah_barang" => $value['jumlah_barang'], ":total_harga" => $value['harga_total']));            
    
                if($jenis_order == 'keranjang'){
                    $keranjang = new Keranjang();
                    $cobaHapusKeranjang = json_decode($keranjang->hapusKeranjangOrder($value['kd_detail_barang'], $kd_user));
                }
            }
    
            $response['Error'] = 0;
            $response['kd_order'] = $kd_order;
            $response['token'] = $pay['body']['token'];
            $jenis_order == 'keranjang' ? $response['cobaHapusKeranjang'] = $cobaHapusKeranjang : '';
            $response['Message'] = "Berhasil Order Barang!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Order Barang!";
            return json_encode($response);
        }
    }

    public function getListOrderSQL($status_order){
        if ($status_order == 'ALL') {
            $sql = "SELECT o.*, u.nama, u.alamat, u.no_telepon, u.kode_pos FROM `order` o 
                        INNER JOIN user u ON o.kd_user=u.kd_user WHERE status_order!=:status_order";
            $result = coreReturnArray($sql, array(":status_order" => self::STATUS_ORDER_WAITING_FOR_PAYMENT));
        } else {
            $sql = "SELECT o.*, u.nama, u.alamat, u.no_telepon, u.kode_pos FROM `order` o 
                        INNER JOIN user u ON o.kd_user=u.kd_user WHERE status_order=:status_order";
            $result = coreReturnArray($sql, array(":status_order" => $status_order));
        }

        if (sizeof($result) > 0) {
            
            $listOrder = [];
            foreach ($result as $key => $value) {
                $sql2 = "SELECT dor.*,db.varian,db.harga,b.nama,b.kd_barang FROM detail_order dor
                        INNER JOIN detail_barang db ON dor.kd_detail_barang=db.kd_detail_barang
                        INNER JOIN barang b ON b.kd_barang=db.kd_barang WHERE dor.kd_order=:kd_order";
                $result2 = coreReturnArray($sql2, array(":kd_order" => $value['kd_order']));

                foreach($result2 as $i => $v) {
                    $file_barang = "SELECT * FROM `file_barang` WHERE kd_barang=:kd_barang ORDER BY created_at ASC";
                    $result_file_barang = coreReturnArray($file_barang, array(":kd_barang" => $v['kd_barang']));

                    if (sizeof($result_file_barang) > 0) {
                        $result2[$i]['file'] = $result_file_barang[0]['file'];
                    }
                }

                $listOrder[] = [
                    'kd_order' => $value['kd_order'],
                    'total_akhir' => $value['total_akhir'],
                    'tanggal_pembayaran' => $value['tanggal_pembayaran'],
                    'jasa_pengiriman' => $value['jasa_pengiriman'],
                    'jenis_pengiriman' => $value['jenis_pengiriman'],
                    'nama' => $value['nama'],
                    'alamat' => $value['alamat'],
                    'no_telepon' => $value['no_telepon'],
                    'kode_pos' => $value['kode_pos'],
                    'status_order' => $value['status_order'],
                    'detail_order' => $result2,
                ];
            }

            $response['listOrder'] = $listOrder;
            $response['Error'] = 0;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function getDetailOrderSQL($kd_order){
        $sql = "SELECT o.*, u.nama AS nama_user, u.alamat, u.no_telepon, u.kode_pos FROM `order` o INNER JOIN user u ON o.kd_user=u.kd_user WHERE o.kd_order=:kd_order";
        $result = coreReturnArray($sql, array(":kd_order" => $kd_order));

        if (sizeof($result) > 0) {
            $result = $result[0];
            $sqlDetailOrder = "SELECT dor.*, db.varian, db.harga, db.berat_satuan, b.nama AS nama_barang, b.kd_barang FROM detail_order dor
                            INNER JOIN detail_barang db ON dor.kd_detail_barang=db.kd_detail_barang
                            INNER JOIN barang b ON b.kd_barang=db.kd_barang WHERE dor.kd_order=:kd_order";
            $resultDetailOrder = coreReturnArray($sqlDetailOrder, array(":kd_order" => $kd_order));

            if (sizeof($resultDetailOrder) > 0) {
                foreach($resultDetailOrder as $key => $val) {
                    $fileBarang = "SELECT * FROM `file_barang` WHERE kd_barang=:kd_barang ORDER BY created_at ASC";
                    $resultFileBarang = coreReturnArray($fileBarang, array(":kd_barang" => $val['kd_barang']));

                    if (sizeof($resultFileBarang) > 0) {
                        $resultDetailOrder[$key]['file'] = $resultFileBarang[0]['file'];
                    }
                }
                $result['detail_order'] = $resultDetailOrder;
            }

            $response['Order'] = $result;
            $response['Error'] = 0;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function getUserOrderSQL($kd_user){
        $sqlOrder = 'SELECT kd_order, created_at, total_akhir, status_order FROM `order` WHERE kd_user=:kd_user ORDER BY created_at DESC';
        $resultOrder = coreReturnArray($sqlOrder, array(":kd_user" => $kd_user));

        if (sizeof($resultOrder) > 0) {
            foreach ($resultOrder as $key => $val) {
                $sqlDetailOrder = "SELECT od.kd_order,
                                          od.kd_detail_barang,
                                          od.jumlah_barang,
                                          od.total_harga,
                                          db.kd_barang,
                                          db.varian,
                                          db.harga,
                                          b.nama
                                    FROM detail_order od
                                    INNER JOIN detail_barang db ON od.kd_detail_barang = db.kd_detail_barang
                                    INNER JOIN barang b ON db.kd_barang = b.kd_barang
                                    WHERE od.kd_order=:kd_order";
                $resultDetailOrder = coreReturnArray($sqlDetailOrder, array(":kd_order" => $val['kd_order']));

                if (sizeof($resultDetailOrder) > 0) {
                    $sqlFiles = 'SELECT * FROM file_barang WHERE kd_barang=:kd_barang ORDER BY created_at ASC LIMIT 1';
                    $resultFiles = coreReturnArray($sqlFiles, array(":kd_barang" => $resultDetailOrder[0]['kd_barang']));
                    if (sizeof($resultFiles) > 0) {
                        $resultDetailOrder[0]['file'] = $resultFiles[0]['file'];
                    }

                    $resultOrder[$key]['barang'] = $resultDetailOrder[0];
                }

                $resultOrder[$key]['jumlah_produk'] = sizeof($resultDetailOrder);
            }
            $response['listOrder'] = $resultOrder;
            $response['Error'] = 0;
            $response['Message'] = 'Data Berhasil Ditemukan!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan!';
            return json_encode($response);
        }
    }

    public function getReportOrderSQL($startDate, $endDate, $returnData = false){
        $sqlOrder = 'SELECT dor.kd_detail_barang, SUM(dor.jumlah_barang) AS total_qty, SUM(dor.total_harga) AS total_harga, db.varian, db.harga, b.kd_barang, b.nama
                    FROM `detail_order` dor
                    INNER JOIN detail_barang db ON db.kd_detail_barang = dor.kd_detail_barang
                    INNER JOIN barang b ON b.kd_barang = db.kd_barang
                    WHERE dor.kd_order IN(SELECT kd_order FROM `order`
                        WHERE created_at BETWEEN "'.$startDate.' 00:00:00" AND "'.$endDate.' 23:59:59"
                        AND status_order = "'.self::STATUS_ORDER_FINISHED.'")
                    GROUP BY dor.kd_detail_barang';
        $resultOrder = coreReturnArray($sqlOrder, null);

        if (sizeof($resultOrder) > 0) {
            foreach ($resultOrder as $key => $item) {
                $getFiles = 'SELECT * FROM file_barang WHERE kd_barang=:kd_barang ORDER BY created_at ASC LIMIT 1';
                $files = coreReturnArray($getFiles, array(":kd_barang" => $item['kd_barang']));
                if (sizeof($files) > 0) {
                    $resultOrder[$key]['file'] = $files[0]['file'];
                }
            }
            if ($returnData) {
                return $resultOrder;
            } else {
                $response['Error'] = 0;
                $response['Order'] = $resultOrder;
                $response['Message'] = 'Data Berhasil Ditemukan';
                return json_encode($response);
            }
        } else {
            if ($returnData) {
                return [];
            } else {
                $response['Error'] = 0;
                $response['Order'] = [];
                $response['Message'] = 'Data Kosong';
                return json_encode($response);
            }
        }
    }
}

class User {
    const LEVEL_BUYER = 'Pembeli';
    const LEVEL_CASHIER = 'Kasir';
    const LEVEL_OWNER = 'Pemilik Toko';

    public function loginSQL($no_telepon, $password){
        $sql = "SELECT * FROM user WHERE no_telepon=:no_telepon AND password=:password AND record_status='A'";
        $result = coreReturnArray($sql, array(":no_telepon" => $no_telepon, ":password" => $password));
    
        if (sizeof($result) > 0) {
            $response['Error'] = 0;
            $response['User'] = $result[0];
            $response['Message'] = 'Login Berhasil!';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Login Gagal!';
            return json_encode($response);
        }
    }

    public function requestResetPassword($email){
        global $API_URL;
        $sql = "SELECT * FROM user WHERE email=:email AND record_status='A'";
        $result = coreReturnArray($sql, array(":email" => $email));
    
        if (sizeof($result) > 0) {
            $to_email = $email;
            $subject = "Ubah Kata Sandi - Toko Ibu";
            $body = '
            Ada masalah saat masuk?
            
            Mengatur ulang kata sandi Anda itu mudah.
            
            Klik link dibawah ini untuk membuat kata sandi baru :
            '.$API_URL.'reset-password?token='.Encryption::encrypt($result[0]['kd_user']).'
            
            Jika Anda tidak membuat permintaan ini, harap abaikan email ini.
            ';

            if (mail($to_email, $subject, $body)) {
                $response['Error'] = 0;
                $response['User'] = $result[0];
                $response['Message'] = 'Silakan periksa kotak masuk email anda';
                return json_encode($response);
            } else {
                $response['Error'] = 0;
                $response['User'] = $result[0];
                $response['Message'] = 'Gagal mengirimkan email';
                return json_encode($response);
            }
        } else {
            $response['Error'] = 1;
            $response['Message'] = 'Email Tidak Ditemukan';
            return json_encode($response);
        }
    }
    
    public function resetPassword($token, $password){
        $kdUser = Encryption::decrypt($token);
        $sql = "UPDATE `user` SET `password`=:password WHERE `kd_user`=:kd_user";
        $result = coreNoReturn($sql, array(":kd_user"=>$kdUser, ":password"=>md5($password)));
    
        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = 'Kata Sandi Berhasil Diganti';
            return json_encode($response);
        }

        $response['Error'] = 1;
        $response['Message'] = 'Gagal Mengganti Kata Sandi';
        return json_encode($response);
    }

    public function detailUser($kd_user){
        $sql = "SELECT * FROM user WHERE kd_user=:kd_user AND record_status=:record_status";
        $result = coreReturnArray($sql, array(":kd_user" => $kd_user, ":record_status" => STATUS_ACTIVE));
    
        if (sizeof($result) > 0) {
            $response['Error'] = 0;
            $response['User'] = $result[0];
            $response['Message'] = 'Data Ditemukan';
            return json_encode($response);
        }else{
            $response['Error'] = 1;
            $response['Message'] = 'Data Tidak Ditemukan';
            return json_encode($response);
        }
    }
    
    public function daftarUserSQL($nama, $no_telepon, $password, $selectedLevel){
        // $random = substr(str_shuffle("0123456789"), 0, 6);
        $level = self::LEVEL_BUYER;
        if ($selectedLevel) {
            $level = $selectedLevel;
        }

        $CariUser = "SELECT * FROM `user` WHERE no_telepon=:no_telepon";
        $resultCariUser = coreReturnArray($CariUser, array(":no_telepon" => $no_telepon));
        if (sizeof($resultCariUser) > 0) {
            $response['Error'] = 1;
            $response['Message'] = "Gagal, Nomor Telepon Telah Terdaftar!";
            return json_encode($response);
        }

        $sql = "INSERT INTO user(nama, no_telepon, password, level) VALUES(:nama, :no_telepon, :password, :level)";
        $result = coreNoReturn($sql, array(":nama" => $nama, ":no_telepon" => $no_telepon, ":password" => $password, ":level" => $level));
            
        if ($result !== null) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mendaftarkan User!";
            $response['data'] = $result;
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mendaftarkan User!";
            return json_encode($response);
        }
    }
    
    public function ubahUserSQL($kd_user, $nama, $no_telepon, $email, $alamat, $kode_pos, $foto_profil, $biteship_area_id){
        if($foto_profil == false){
            $sql = "UPDATE `user` SET `nama`=:nama, 
                                        `email`=:email,
                                        `no_telepon`=:no_telepon,
                                        `alamat`=:alamat,
                                        `kode_pos`=:kode_pos,
                                        `biteship_area_id`=:biteship_area_id
                                    WHERE `kd_user`=:kd_user";
            $result = coreNoReturn($sql, array(":kd_user"=>$kd_user, ":nama"=>$nama, ":email"=>$email, 
            ":no_telepon"=>$no_telepon, ":alamat"=>$alamat, ":kode_pos"=>$kode_pos, ":biteship_area_id" => $biteship_area_id));
        }else{
            $fp = uploadFileSQL2($foto_profil);
            $sql = "UPDATE `user` SET `nama`=:nama, 
                                        `email`=:email,
                                        `no_telepon`=:no_telepon,
                                        `alamat`=:alamat,
                                        `foto_profil`=:foto_profil,  
                                        `kode_pos`=:kode_pos,
                                        `biteship_area_id`=:biteship_area_id
                                    WHERE `kd_user`=:kd_user";
            $result = coreNoReturn($sql, array(":kd_user"=>$kd_user, ":nama"=>$nama, ":email"=>$email, 
            ":no_telepon"=>$no_telepon, ":alamat"=>$alamat, ":foto_profil"=>$fp, ":kode_pos"=>$kode_pos, ":biteship_area_id" => $biteship_area_id));
            // $response['fp'] = $fp;
        }
        
        if ($result['success'] == 1) {

            $SQLuser = "SELECT * FROM `user` WHERE kd_user=:kd_user";
            $result_user = coreReturnArray($SQLuser, array(":kd_user" => $kd_user));
            if (sizeof($result_user) > 0) {
                $response['user'] = $result_user[0];
            }

            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mengubah User!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mengubah User!";
            return json_encode($response);
        }
    }

    public function ubahStatusUserSQL($kd_user, $record_status){
        $sql = "UPDATE `user` SET `record_status`=:record_status WHERE `kd_user`=:kd_user";
        $result = coreNoReturn($sql, array(":kd_user"=>$kd_user, ":record_status"=>$record_status));
        
        if ($result['success'] == 1) {
            $response['Error'] = 0;
            $response['Message'] = "Berhasil Mengubah Status User!";
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = "Gagal Mengubah Status User!";
            return json_encode($response);
        }
    }

    public function getListUser($level, $nama){
        $sql = 'SELECT * FROM user WHERE record_status=:record_status ';
        if ($level) {
            $sql .= "AND `level`='".$level."' ";
        } else {
            $sql .= "AND `level` IN ('".self::LEVEL_CASHIER."', '".self::LEVEL_OWNER."') ";
        }
        if ($nama) {
            $sql .= "AND `nama` LIKE '%".$nama."%' ";
        }
        $sql .= 'ORDER BY created_at DESC';
        $items = coreReturnArray($sql, array(":record_status"=>STATUS_ACTIVE));

        $response['Error'] = 0;
        $response['User'] = $items;
        $response['Message'] = 'success';
        return json_encode($response);
    }

    public function changePassword($kdUser, $oldPassword, $newPassword){
        $sql = "SELECT * FROM user WHERE kd_user=:kd_user AND password=:password AND record_status='A'";
        $result = coreReturnArray($sql, array(":kd_user" => $kdUser, ":password" => $oldPassword));
    
        if (sizeof($result) > 0) {
            $sql = "UPDATE `user` SET `password`=:password WHERE `kd_user`=:kd_user";
            $result = coreNoReturn($sql, array(":kd_user"=>$kdUser, ":password"=>md5($newPassword)));

            if ($result['success'] == 1) {
                $response['Error'] = 0;
                $response['Message'] = 'Kata Sandi Berhasil Diganti';
                return json_encode($response);
            }

            $response['Error'] = 1;
            $response['Message'] = 'Gagal Mengganti Kata Sandi';
            return json_encode($response);
        } else {
            $response['Error'] = 1;
            $response['Message'] = 'Kata Sandi Lama Tidak Sesuai';
            return json_encode($response);
        }
    }
}

class Midtrans {
    public function createToken($userCode, $total)
    {
        $snapData = [
            'transaction_details' => [
                'order_id' => $userCode . random_int(100000, 999999),
                'gross_amount' => $total,
            ],
        ];
        $midtrans = new MidtransApi();
        $pay = $midtrans->request(MidtransApi::TYPE_SNAP, 'POST', '/snap/v1/transactions', $snapData);
        $response['status'] = 201;
        $response['message'] = "Successfully Added Order To Midtrans";
        $response['data'] = [
            'token' => $pay['body']['token'],
        ];
        return json_encode($response);
    }
}

class Biteship {
    public function getMaps($input = '')
    {
        $biteship = new BiteshipApi();
        $req = $biteship->request('GET', "/v1/maps/areas?countries=ID&input=$input&type=single");
        $response['status'] = 200;
        $response['message'] = "success";
        $response['data'] = $req['body']['areas'];
        return json_encode($response);
    }
    public function getCouriers()
    {
        $biteship = new BiteshipApi();
        $req = $biteship->request('GET', "/v1/couriers");
        $response['status'] = 200;
        $response['message'] = "success";
        $response['data'] = $req['body']['couriers'];
        return json_encode($response);
    }
    public function getRates($destinationAreaId, $couriers, $items)
    {
        $data = [
            'origin_area_id' => BiteshipApi::ORIGIN_AREA_ID,
            'destination_area_id' => $destinationAreaId,
            'couriers' => $couriers,
            'items' => $items,
        ];
        $biteship = new BiteshipApi();
        $req = $biteship->request('POST', '/v1/rates/couriers', $data);
        $response['status'] = 200;
        $response['message'] = "success";
        $response['data'] = $req['body']['pricing'];
        return json_encode($response);
    }
    public function getTracking($waybillId = '', $courier = '')
    {
        $biteship = new BiteshipApi();
        $req = $biteship->request('GET', "/v1/trackings/$waybillId/couriers/$courier");
        $response['status'] = 200;
        $response['message'] = "success";
        $response['data'] = $req['body']['history'];
        return json_encode($response);
    }
}

function hilangSimbol($name){
    return str_replace(['!','@','#','$','%','^','&','*',' ', "'"],"",$name);
}

function getLastIdTable($idField, $table){
    $sql = "SELECT ".$idField." FROM `".$table."` ORDER BY created_at DESC LIMIT 1";
    $result = coreReturnArray($sql, null);

    if (sizeof($result) > 0) {
        $response['data'] = (int)filter_var($result[0][$idField], FILTER_SANITIZE_NUMBER_INT)+1;
        $response['Error'] = 0;
        $response['Message'] = 'Data Berhasil Ditemukan!';
        return json_encode($response);
    }else{
        $response['data'] = 1;
        $response['Error'] = 1;
        $response['Message'] = 'Data Berhasil Ditemukan!';
        return json_encode($response);
    }
}   

function compress_image($source_url, $destination_url, $quality) {

    $info = getimagesize($source_url);

    if ($info['mime'] == 'image/jpeg')
    $image = imagecreatefromjpeg($source_url);

    elseif ($info['mime'] == 'image/gif')
    $image = imagecreatefromgif($source_url);

    elseif ($info['mime'] == 'image/png')
    $image = imagecreatefrompng($source_url);

    imagejpeg($image, $destination_url, $quality);
    return $destination_url;
}

function uploadFileSQL2($file){

    $valid_ext = array('png','jpeg','jpg');
    $random = substr(str_shuffle("0123456789"), 0, 6);
    $loc = "assets/file/".date("Y/m/d")."/";
    if (!file_exists($loc)) {
        mkdir($loc, 0777, true);
    }

    $filename = $file['name'];    
    $filename = hilangSimbol($filename);
    // file extension
    $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
    $file_extension = strtolower($file_extension);
    $path = $loc.$random.$filename;

    if(in_array($file_extension, $valid_ext)){  
        $moveFile = compressImage($file['tmp_name'], $path, 50);
    }else{
        $moveFile = move_uploaded_file($file['tmp_name'], $path);
    }
    
    if($moveFile){
        return WEB_SERVER . DIR_API . '/' . $path;
    }
       
}

function compressImage($source, $destination, $quality) {

    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') 
        $image = imagecreatefromjpeg($source);

    elseif ($info['mime'] == 'image/gif') 
        $image = imagecreatefromgif($source);

    elseif ($info['mime'] == 'image/png') 
        $image = imagecreatefrompng($source);

    return imagejpeg($image, $destination, $quality);
}

?>