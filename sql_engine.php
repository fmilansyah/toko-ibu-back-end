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

$serverName = $_SERVER['SERVER_NAME'];
if ($serverName == 'localhost') {
    // local
    $API_URL = "//localhost". DIR_API_LOCAL;
    $globalVar = $GLOBALS[$serverName];
} else {
    // production
    $API_URL = "//".$serverName. DIR_API_PRO;
    $globalVar = $GLOBALS[$delSlash]; 
}


function getDataBarangSQL(){
    $sql = "SELECT * FROM barang ORDER BY created_at DESC";
    $result = coreReturnArray($sql, null);

    if (sizeof($result) > 0) {
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

function getDetailBarangSQL($kd_barang){
    $detail_barang = "SELECT * FROM `detail_barang` WHERE kd_barang=:kd_barang ORDER BY created_at DESC";
    $result_detail_barang = coreReturnArray($detail_barang, array(":kd_barang" => $kd_barang));

    $file_barang = "SELECT * FROM `file_barang` WHERE kd_barang=:kd_barang ORDER BY created_at DESC";
    $result_file_barang = coreReturnArray($file_barang, array(":kd_barang" => $kd_barang));

    if (sizeof($result_detail_barang) > 0 || sizeof($result_file_barang) > 0) {
        $response['Error'] = 0;
        
        if (sizeof($result_detail_barang) > 0) {
            $response['detail_barang'] = $result_detail_barang;
        }
        if (sizeof($result_file_barang) > 0) {
            $response['file_barang'] = $result_file_barang;
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


function hilangSimbol($name){
    return str_replace(['!','@','#','$','%','^','&','*',' ', "'"],"",$name);
}

function tambahDataBarangSQL($kd_barang, $nama, $ukuran, $listFile){
    
    $sql = "INSERT INTO barang(kd_barang, nama) VALUES(:kd_barang, :nama)";
    $result = coreNoReturn($sql, array(":kd_barang" => $kd_barang, ":nama" => $nama));
        
    if ($result['success'] == 1) {
        
        $jumlah_ukuran = sizeof($ukuran);
        if($jumlah_ukuran > 0){
            $hitung_input_ukuran = 0;
            foreach ($ukuran as $key => $data) {
                $sql_i_Ukuran = "INSERT INTO `detail_barang`(`kd_detail_barang`, `kd_barang`, `varian`, `stok`, `harga`) 
                        VALUES (:kd_detail_barang, :kd_barang, :varian, :stok, :harga)";
                $result_i_ukuran = coreNoReturn($sql_i_Ukuran, array(
                                                    ":kd_detail_barang" => $data['kd_detail_barang'], 
                                                    ":kd_barang" => $kd_barang, 
                                                    ":varian" => $data['varian'],
                                                    ":stok" => $data['stok'],
                                                    ":harga" => $data['harga']
                ));
                if ($result_i_ukuran['success'] == 1) {
                    $hitung_input_ukuran++;
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
                
                $tipe = $listFile['type'][$keyFile];
                $fileName = date("YmdHis"). "-" .$in; 
                $fileName = hilangSimbol($fileName);

                $fileDir = $GLOBALS['API_URL'].'/assets/file/'.date("Y/m/d").'/';
                $kd_file = substr(str_shuffle("0123456789"), 0, 8);

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

function ubahDataBarangSQL($kd_barang, $nama, $ukuran, $hapus_ukuran, $hapus_file, $listFile){
    
    $sql = "UPDATE `barang` SET `nama`=:nama WHERE `kd_barang`=:kd_barang";
    $result = coreNoReturn($sql, array(":kd_barang"=>$kd_barang, ":nama"=>$nama));
        
    if ($result['success'] == 1) {

        foreach ($hapus_ukuran as $key => $value) {
            $sql_delete_ukuran = "DELETE FROM detail_barang WHERE `kd_detail_barang`=:kd_detail_barang";
            $result_delete_ukuran = coreNoReturn($sql_delete_ukuran, array(":kd_detail_barang"=>$value));
        }

        foreach ($hapus_file as $key => $value) {
            $sql_delete_file = "DELETE FROM file_barang WHERE `kd_file`=:kd_file";
            $result_delete_file = coreNoReturn($sql_delete_file, array(":kd_file"=>$value));
        }

        $jumlah_ukuran = sizeof($ukuran);
        if($jumlah_ukuran > 0){
            $hitung_input_ukuran = 0;
            foreach ($ukuran as $key => $data) {
                $sql_i_Ukuran = "INSERT INTO `detail_barang`(`kd_detail_barang`, `kd_barang`, `varian`, `stok`, `harga`) 
                        VALUES (:kd_detail_barang, :kd_barang, :varian, :stok, :harga)
                        ON DUPLICATE KEY 
                        UPDATE `varian`= :varian2, `stok`= :stok2, `harga`= :harga2";
                $result_i_ukuran = coreNoReturn($sql_i_Ukuran, array(
                                                    ":kd_detail_barang" => $data['kd_detail_barang'], 
                                                    ":kd_barang" => $kd_barang, 
                                                    ":varian" => $data['varian'],
                                                    ":varian2" => $data['varian'],
                                                    ":stok" => $data['stok'],
                                                    ":stok2" => $data['stok'],
                                                    ":harga" => $data['harga'],
                                                    ":harga2" => $data['harga']
                ));
                if ($result_i_ukuran['success'] == 1) {
                    $hitung_input_ukuran++;
                }
            }
            if($jumlah_ukuran == $hitung_input_ukuran){
                $response['Message_Ukuran'] = "Berhasil Mengubah Ukuran Barang!";
            }else{
                $response['Message_Ukuran'] = "Gagal Mengubah Ukuran Barang!";
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
                $kd_file = substr(str_shuffle("0123456789"), 0, 8);

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



function deleteDataBarangSQL($kd_barang){
    
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

function uploadFileSQL2($file){

    $valid_ext = array('png','jpeg','jpg');
    $random = substr(str_shuffle("0123456789"), 0, 6);
    $loc = "assets/file/".date("Y/m/d")."/";
    if (!file_exists($loc)) {
        mkdir($loc, 0777, true);
    }

    $filename = $file['name'];    
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

function uploadFileSQL($file){
    $loc = "assets/file/".date("Y/m/d")."/";
    if (!file_exists($loc)) {
        mkdir($loc, 0777, true);
    }

    $path = $file['name'];
    // $path = $_FILES['file']['name'];
    $type = pathinfo($path, PATHINFO_EXTENSION);
    
    $random = substr(str_shuffle("0123456789"), 0, 6);
    $fbName = $random . "-" .$file['name']; 
    $url =  __DIR__ ."/".$loc.$fbName; 

    if($type == 'jpg' || $type == 'png' || $type == 'jpeg'){
        $moveFile = compress_image($file['tmp_name'], $url, 50);
    }else{
        $moveFile = move_uploaded_file($file['tmp_name'], $loc.$fbName);
    }
    
    if ($moveFile) {
        $hasil = $loc.$fbName;
        $res["Error"] = 0;
        $res["Message"] = "Berhasil upload file!";
        $res["hasil"] = $loc.$fbName;
        $res["type"] = $type;
        echo json_encode($res);
    } else {
        $res["Error"] = 1;
        $res["Message"] = "Gagal upload file!";
        echo json_encode($res);
    }
}

?>