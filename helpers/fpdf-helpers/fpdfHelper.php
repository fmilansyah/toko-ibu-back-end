<?php
ini_set( 'default_charset', 'UTF-8' );
ini_set('memory_limit', '-1');
date_default_timezone_set('Asia/Jakarta');

$dirConfig = '';
$dirfpdf = '';
if (strpos(__DIR__, '/fpdf-helpers') !== false) {
   $dirConfig = str_replace('/fpdf-helpers', '', __DIR__);
   $dirfpdf = str_replace('/helpers/fpdf-helpers', '', __DIR__);
}
if (strpos(__DIR__, '\fpdf-helpers') !== false) {
   $dirConfig = str_replace('\fpdf-helpers', '', __DIR__);
   $dirfpdf = str_replace('\helpers\fpdf-helpers', '', __DIR__);
}

require_once $dirConfig . '/helper-config.php';
require_once $dirfpdf . '/lib/fpdf/fpdf.php';

function testPatidananFpdf($idPatidana){
   $dataFoto = getFotoPatidana($idPatidana);
   $dataNama = getNamaPatidana($idPatidana);

   $page = array(15,30); // 15x30cm / 'A4'
   $pdf = new FPDF('P','cm',$page);
   $pdf->AddPage();
   // define('FPDF_FONTPATH', $GLOBALS['dirfpdf'] . '/lib/fpdf/font');
   // $pdf->AddFont('Pali', '', 'Pali-Regular.php');
   // $pdf->SetFont('Pali','',16); 
   $pdf->AddFont('MicrosoftYaHei', '', 'MicrosoftYaHei.php');
   $pdf->SetFont('MicrosoftYaHei','',16); 
   // $pdf->AddFont('SansitaSwashed-Regular', '', 'SansitaSwashed-Regular.php');
   // $pdf->SetFont('SansitaSwashed-Regular','',16); 

   foreach ($dataFoto as $key => $value) {
      $x = px2cm($value['xPdf']);
      $y = px2cm($value['yPdf']);
      $width  = $value['width'] ? px2cm(str_replace('px','',$value['width'])) : px2cm(250);
      $pdf->Image('http:'.$value['file'],$x, $y, $width);
   }

   foreach ($dataNama as $key => $value) {
      $x = nolplus(px2cm($value['xPdf']));
      $y = nolplus(px2cm($value['yPdf']), 'y');
      $nama = $value['Nama'];
      $pdf->SetFontSize(px2pt(str_replace('px','',$value['FontSize'])));
      $pdf->Text($x, $y, $nama);
   }

   // $pdf->SetDrawColor(245, 54, 92);
   $pdf->Line(0, 0, 0, 30);
   $pdf->Line(15, 0, 15, 30);

   $pdf->Output('D', 'fpdf-'.$idPatidana.'.pdf');
}

function exportPatidana($idBooking, $typeExport){
   // $typeExport = D/F
   // D = download
   // F = save file
   
   $nameFile = '';
   $dataPatidana = getBookingPatidana($idBooking);
   foreach ($dataPatidana as $key => $value) {
      $nameFile = $value['KodePemesanan'] .'-'. getKodePaket($value['NamaBarang']) .'-'. $key + 1 .'.pdf';
   }
}

function getBookingPatidana($idBooking){
   $sql = "SELECT ba.KodePemesanan, bo.IdPatidana, bp.NamaBarang FROM booking_acara ba
            INNER JOIN bk_order bo
               ON bo.IdBooking = ba.Id
            LEFT JOIN bk_product bp
               ON bp.Id = bo.IdProduct
            WHERE ba.Id = :Id";
   return coreReturnArray($sql, array(":Id" => $idBooking));
}

function getDetailBookingPatidana($idBooking){
   $sql = "SELECT ba.KodePemesanan, bo.IdPatidana, bp.NamaBarang, pf.* FROM booking_acara ba
            INNER JOIN bk_order bo
               ON bo.IdBooking = ba.Id
            LEFT JOIN bk_product bp
               ON bp.Id = bo.IdProduct
            INNER JOIN patidana_foto pf
               ON pf.IdJawaban = bo.IdPatidana
            WHERE ba.Id = :Id";
   $detail = coreReturnArray($sql, array(":Id" => $idBooking));
   $response['Detail'] = $detail;

   return $response;
}

function getKodePaket($namaBarang){
   if ($namaBarang == 'Paket altar Chanda') {
      return 'Chanda';
   } else if ($namaBarang == 'Paket altar Viriya') {
      return 'Viriya';
   } else if ($namaBarang == 'Paket altar Citta') {
      return 'Citta';
   } else {
      return 'Vimamsa';
   }
}

function getFotoPatidana($idPatidana){
   $sql = "SELECT * FROM patidana_foto WHERE IdJawaban = :IdJawaban";
   return coreReturnArray($sql, array(":IdJawaban" => $idPatidana));
}

function getNamaPatidana($idPatidana){
   $sql = "SELECT * FROM patidana_nama WHERE IdJawaban = :IdJawaban";
   return coreReturnArray($sql, array(":IdJawaban" => $idPatidana));
}

function px2cm($px){
   if($px == null) {
      return 0;
   }
   $convertCM = 0.0264583333;
   return $px * $convertCM;
}

function px2pt($px){
   if($px == null) {
      return 30;
   }
   $convertCM = 0.75;
   return $px * $convertCM;
}

function nolplus($value, $type = 'x'){
   $numberOptional = 0.7;
   if($value <= 0.5){
      return floatval($value) + $numberOptional;
   } else {
      if($type == 'y'){
         return floatval($value) + $numberOptional;
      } else {
         return $value;
      }
   }
}