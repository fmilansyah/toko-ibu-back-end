<?php
ini_set( 'default_charset', 'UTF-8' );
ini_set('memory_limit', '-1');
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/helper-config.php';

function addScheduleAcaraHelper($keyParams, $idAcara, $levelStatus = '1'){
   $status = '';
   $xSchedule = 0;
   $sqlSchedule = "SELECT sa.* FROM schedule_acara sa WHERE sa.IdAcara = :IdAcara AND sa.LevelStatus = (SELECT MAX(sa2.LevelStatus) FROM schedule_acara sa2 WHERE sa.IdAcara = sa2.IdAcara)";
   $rsqlSchedule = coreReturnArray($sqlSchedule, array(":IdAcara" => $idAcara));

   if(sizeof($rsqlSchedule) == 0){
      return false;
   }

   $sqlDeleteTempSchedule = "DELETE FROM temp_schedule_acara WHERE IdAcara = :IdAcara";
   $rsqlDeleteTempSchedule = coreNoReturn($sqlDeleteTempSchedule, array(":IdAcara" => $idAcara));
   if($rsqlDeleteTempSchedule['success'] == 0){
      error_log('Delete temp ' .$idAcara. 'gagal!', 0);
   }

   $intervalAcara = getIntervalAcara($idAcara);
   if($intervalAcara == ''){
      return false;
   }

   
   foreach ($rsqlSchedule as $key => $value) {
      $idAcaraNew = 'ACR'. date("ymdHis") . $key;
      
      $formatInterval = formatIntervalHelper($intervalAcara);
      $tanggalAcara['start'] = $value['Tanggal'];
      if($formatInterval !== ''){
           $tanggalAcara['end'] = date('c', strtotime($value['Tanggal']. '+' . $formatInterval));
      } else {
          // jika date mulai dan selesai sama
          $tanggalAcara['end'] = $value['Tanggal'];
      }

      $index = $key+1;
      $levelStatus = $value['LevelStatus'];
      $insertAcara = insertAcara($idAcara, $idAcaraNew, $levelStatus, $tanggalAcara);
      if ($insertAcara == '') {
         $status .= insertListTemplateAcara($idAcara, $idAcaraNew, $index, $levelStatus);
         $status .= insertFormulirAcara($idAcara, $idAcaraNew, $index, $levelStatus);
         $status .= insertBiaya($idAcara, $idAcaraNew, $index, $levelStatus);
         $status .= insertDonasi($idAcara, $idAcaraNew, $index, $levelStatus);
         $addDetailTemplateAcara = addDetailTemplateAcara($idAcara, $idAcaraNew, $index, $levelStatus);
   
         if ($addDetailTemplateAcara == '') {
            // diinsert cuma sekali tidak terpengaruh levelstatus
            $status .= addLogAcaraSchedule($idAcara, $idAcaraNew, $index, $levelStatus, $value['CreatedBy']);
            $insertLaporanAcara = insertLaporanAcara($idAcara, $idAcaraNew, $index, $levelStatus);
   
            $xSchedule++;
         } else {
            error_log($addDetailTemplateAcara, 0);
         }
      }
   }

   if($xSchedule == sizeof($rsqlSchedule)){
      return $status;
   } else {
      return $status;
   }
}

function getIntervalAcara($idAcara){
   $sqlAcara = "SELECT TanggalStart, TanggalEnd FROM acara WHERE Id = :IdAcara LIMIT 1";
   $rsqlAcara = coreReturnArray($sqlAcara, array(":IdAcara" => $idAcara));

   if(sizeof($rsqlAcara) > 0){
      $start = new DateTime($rsqlAcara[0]['TanggalStart']);
      $end = new DateTime($rsqlAcara[0]['TanggalEnd']);
      return $start->diff($end);
   } else {
      return '';
   }
}

function formatIntervalHelper($interval) {
   $result = "";
   if ($interval->y) { $result .= $interval->format("%y years "); }
   if ($interval->m) { $result .= $interval->format("%m months "); }
   if ($interval->d) { $result .= $interval->format("%d days "); }
   if ($interval->h) { $result .= $interval->format("%h hours "); }
   if ($interval->i) { $result .= $interval->format("%i minutes "); }
   if ($interval->s) { $result .= $interval->format("%s seconds "); }

   return $result;
}

function insertListTemplateAcara($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';

   $sqlCore = "SELECT * FROM list_template_acara WHERE IdAcara = :IdAcara";
   $rsqlCore = coreReturnArray($sqlCore, array(":IdAcara" => $idAcara));

   if(sizeof($rsqlCore) == 0){
      $status = 'list template acara kosong';
      return $status;
   }

   $x = 0;
   $sqlListTemplateAcara = "INSERT INTO list_template_acara(`Id`, `IdAcara`, `IdTemplate`, `NamaTemplate`, `RangeDay`, `LevelStatus`) VALUE (:Id, :IdAcara, :IdTemplate, :NamaTemplate, :RangeDay, :LevelStatus)";
   foreach($rsqlCore as $key => $vListTemplate){
      if(!isset(${"idList" . $vListTemplate['Id']})){
         ${"idList" . $vListTemplate['Id']} = 'L' . date("ymdHis") . $key . $index;
      }

      $resultAcara = coreNoReturn($sqlListTemplateAcara, array(
                                                         ":Id" => ${"idList" . $vListTemplate['Id']}, 
                                                         ":IdAcara" => $idAcaraNew, 
                                                         ":IdTemplate" => $vListTemplate['IdTemplate'], 
                                                         ":NamaTemplate" => $vListTemplate['NamaTemplate'], 
                                                         ":RangeDay" => $vListTemplate['RangeDay'],
                                                         ":LevelStatus" => $vListTemplate['LevelStatus']
                                                      ));
      if($resultAcara['success'] == 1){
         $x++;
      } else {
         error_log("INSERT list_template_acara " . $resultAcara['message'], 0);
      }
   }

   if($x !== sizeof($rsqlCore)){
      $status = 'Gagal insert list template acara';
   }

   return $status;
}

function insertAcara($idAcara, $idAcaraNew, $levelStatus, $tanggalAcara){
   $status = '';

   $sqlCore = "SELECT * FROM acara WHERE Id = :IdAcara";
   $rsqlCore = coreReturnArray($sqlCore, array(":IdAcara" => $idAcara));

   if(sizeof($rsqlCore) == 0){
      $status = 'data acara tidak ada';
      error_log('Insert ' - $idAcara . ' - ' .$status, 0);
      return $status;
   }

   foreach ($rsqlCore as $key => $value) {
      $sqlAcara = "INSERT INTO `acara`(`Id`, `ParentId`, `IdSubweb`, `NamaAcara`, `TanggalStart`, `TanggalEnd`, `Persiapan`, `ReminderPersiapan`, `IntervalReminderPersiapan`, `ListTemplateAcara`, `Biaya`, `Catatan`, `UrlYt`, `UrlZoom`, `LevelStatus`, `Status`, `PasswordLaporanAcara`, `CreatedBy`) 
               VALUES (:Id, :ParentId, :IdSubweb, :NamaAcara, :TanggalStart, :TanggalEnd, :Persiapan, :ReminderPersiapan, :IntervalReminderPersiapan, :ListTemplateAcara, :Biaya, :Catatan, :UrlYt, :UrlZoom, :LevelStatus,  :Status, :PasswordLaporanAcara, :CreatedBy)";
      $resultAcara = coreNoReturn($sqlAcara, array(
                                          ":Id" => $idAcaraNew, 
                                          ":ParentId" => $idAcara, 
                                          ":IdSubweb" => $value['IdSubweb'], 
                                          ":NamaAcara" => $value['NamaAcara'],
                                          ":TanggalStart" => $tanggalAcara['start'],
                                          ":TanggalEnd" => $tanggalAcara['end'],
                                          ":Persiapan" => $value['Persiapan'],
                                          ":ReminderPersiapan" => $value['ReminderPersiapan'],
                                          ":IntervalReminderPersiapan" => $value['IntervalReminderPersiapan'],
                                          ":ListTemplateAcara" => $value['ListTemplateAcara'],
                                          ":Biaya" => $value['Biaya'],
                                          ":Catatan" => $value['Catatan'],
                                          ":UrlYt" => $value['UrlYt'],
                                          ":UrlZoom" => $value['UrlZoom'],
                                          ":LevelStatus" => $value['LevelStatus'],
                                          ":Status" => $value['Status'],
                                          ":PasswordLaporanAcara" => $value['PasswordLaporanAcara'],
                                          ":CreatedBy" => $value['CreatedBy']
                                    ));
                                    
      if($resultAcara['success'] == 0){
         $status = 'Gagal insert acara';
         error_log('Insert - '. $idAcara . ' - ' .$status, 0);
      }
   }

   return $status;
}

function insertFormulirAcara($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';

   $sqlCore = "SELECT * FROM detail_acara_formulir WHERE IdAcara = :IdAcara";
   $rsqlCore = coreReturnArray($sqlCore, array(":IdAcara" => $idAcara));

   $x = 0;
   foreach ($rsqlCore as $key => $value) {
      if(!isset(${"idFormulir" . $value['Id']})){
         ${"idFormulir" . $value['Id']} = 'DAF'. date("ymdHis") . $key . $index;
      }

      $sqlInsert = "INSERT INTO detail_acara_formulir(Id, IdAcara, IdSubweb, FormId, LinkRedirect, LinkReturn, RangeDay, LevelStatus)
                  VALUES(:Id, :acara, :sub, :formId, :LinkRedirect, :LinkReturn, :RangeDay, :LevelStatus)";

      $resultInsertForm = coreNoReturn($sqlInsert, array( 
                  ":Id" => ${"idFormulir" . $value['Id']}, 
                  ":acara" => $idAcaraNew, 
                  ":sub" => $value['IdSubweb'],
                  ":formId" => $value['FormId'],
                  ":LinkRedirect" => $value['LinkRedirect'],
                  ":LinkReturn" => $value['LinkReturn'],
                  ":RangeDay" => $value['RangeDay'],
                  ":LevelStatus" => $value['LevelStatus']
      ));

      if($resultInsertForm['success'] == 1){
         $x++;
      } else {
         error_log("INSERT detail_acara_formulir " . $resultInsertForm['message'], 0);
      }
   }

   if($x !== sizeof($rsqlCore)){
      $status = 'Gagal insert formulir acara';
   }

   return $status;
}

function insertBiaya($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $levelStatusBiaya = (int) $levelStatus + 2;

   $sqlCore = "SELECT * FROM biaya WHERE IdAcara = :IdAcara";
   $rsqlCore = coreReturnArray($sqlCore, array(":IdAcara" => $idAcara));

   $x = 0;
   foreach($rsqlCore as $key => $value){
      if(!isset(${"id" . $value['Id']})){
         ${"id" . $value['Id']} = substr(str_shuffle("0123456789"), 0, 5);
      }

      $sql = "INSERT INTO `biaya`(`Id`, `IdSubweb`, `IdAcara`, `IdTemplate`, `IdMaster`, `Name`, `Biaya`, `Jumlah`, `LevelStatus`, `RangeDay`, `CreatedBy`) 
               VALUES (:Id, :IdSubweb, :IdAcara, :IdTemplate, :IdMaster, :Name, :Biaya, :Jumlah, :LevelStatus, :RangeDay, :CreatedBy)";
      $result = coreNoReturn($sql, array(
                              ":Id" => ${"id" . $value['Id']}, 
                              ":IdSubweb" => $value['IdSubweb'], 
                              ":IdAcara" => $idAcaraNew,
                              ":IdTemplate" => $value['IdTemplate'],
                              ":IdMaster" => $value['IdMaster'],
                              ":Name" => $value['Name'],
                              ":Biaya" => $value['Biaya'],
                              ":Jumlah" => $value['Jumlah'],
                              ":LevelStatus" => $value['LevelStatus'],
                              ":RangeDay" => $value['RangeDay'],
                              ":CreatedBy" => $value['CreatedBy']
                           ));
      if($result['success'] == 1){
         $x++;
      } else {
         error_log("INSERT biaya " . $result['message'], 0);
      }
   }

   if($x !== sizeof($rsqlCore)){
      $status = 'Gagal insert biaya acara';
   }

   return $status;
}

function insertDonasi($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';

   $sqlCore = "SELECT * FROM donasi_acara WHERE IdAcara = :IdAcara";
   $rsqlCore = coreReturnArray($sqlCore, array(":IdAcara" => $idAcara));

   $x = 0;
   foreach($rsqlCore as $key => $value){
      if(!isset(${"idDonasi" . $value['Id']})){
         ${"idDonasi" . $value['Id']} = date("ymdHis") . $key . $index;
      }

      $sqlDonasi = "INSERT INTO `donasi_acara`(`Id`, `IdAcara`, `IdSubweb`, `IdBiaya`, `IdUser`, `UsernameDonasi`, `TypeDonasi`, `IdBarangDonasi`, `NamaBarangDonasi`, `Amount`, `RangeDay`, `LevelStatus`)
                VALUES (:Id, :IdAcara, :IdSubweb, :IdBiaya, :IdUser, :UsernameDonasi, :TypeDonasi, :IdBarangDonasi, :NamaBarangDonasi, :Amount, :RangeDay, :LevelStatus)";
      $resultdonasi = coreNoReturn($sqlDonasi, array( 
                              ":Id" => ${"idDonasi" . $value['Id']}, 
                              ":IdAcara" => $idAcaraNew, 
                              ":IdSubweb" => $value['IdSubweb'],
                              ":IdBiaya" => $value['IdBiaya'] == $value['IdAcara'] ? $idAcaraNew : $value['IdBiaya'], 
                              ":IdUser" => $value['IdUser'],
                              ":UsernameDonasi" => $value['UsernameDonasi'],
                              ":TypeDonasi" => $value['TypeDonasi'],
                              ":IdBarangDonasi" => $value['IdBarangDonasi'],
                              ":NamaBarangDonasi" => $value['NamaBarangDonasi'],
                              ":Amount" => $value['Amount'],
                              ":RangeDay" => $value['RangeDay'],
                              ":LevelStatus" => $value['LevelStatus']
                        ));
      if($resultdonasi['success'] == 1){
         $x++;
      } else {
         error_log("INSERT donasi_acara " . $resultdonasi['message'], 0);
      }
   }

   if($x !== sizeof($rsqlCore)){
      $status = 'Gagal insert donasi acara';
   }

   return $status;
}

function addDetailTemplateAcara($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   
   $status .= detailAcaraRuangan($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraPetugas($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraNotif($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraKonsumsi($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraDekorasi($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraPerlengkapan($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraTransportasi($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraKomunikasi($idAcara, $idAcaraNew, $index, $levelStatus);
   $status .= detailAcaraDokumentasi($idAcara, $idAcaraNew, $index, $levelStatus);

   return $status;
}

function detailAcaraRuangan($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplateRuangan = 0;
   $sqlTRuangan = "SELECT * FROM detail_acara_template_ruangan WHERE IdAcara = :IdAcara";
   $rsqlTRuangan = coreReturnArray($sqlTRuangan, array(":IdAcara" => $idAcara));
   foreach($rsqlTRuangan as $key => $vruangan){
      if(!isset(${"idTemplate" . $vruangan['Id']})){
         ${"idTemplate" . $vruangan['Id']} = 'DATR'. date("ymdHis") . $key . $index;
      }

      $sqlDetailTemplateRuangan = "INSERT INTO `detail_acara_template_ruangan`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateRuangan`, `MaxKapasitas`, `Biaya`, `Catatan`, `RangeDay`, `LevelStatus`) 
                      VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateRuangan, :MaxKapasitas, :Biaya, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplateRuangan, array( 
                                          ":Id" => ${"idTemplate" . $vruangan['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vruangan['IdSubweb'],
                                          ":IdTemplateRuangan" => $vruangan['IdTemplateRuangan'],
                                          ":MaxKapasitas" => $vruangan['MaxKapasitas'],
                                          ":Biaya" => $vruangan['Biaya'],
                                          ":Catatan" => $vruangan['Catatan'],
                                          ":RangeDay" => $vruangan['RangeDay'],
                                          ":LevelStatus" => $vruangan['LevelStatus']
                                      ));
      if($result['success'] == 1){
          $xTemplateRuangan++;
      } else {
         error_log("INSERT detail_acara_template_ruangan " . $result['message'], 0);
      }
   }

   if ($xTemplateRuangan !== sizeof($rsqlTRuangan)) {
      $status .= 'gagal insert detail acara template ruangan. '; 
   }

   $xMasterRuangan = 0;
   $sqlMRuangan = "SELECT * FROM detail_acara_master_ruangan WHERE IdAcara = :IdAcara";
   $rsqlMRuangan = coreReturnArray($sqlMRuangan, array(":IdAcara" => $idAcara));
   foreach($rsqlMRuangan as $key => $vruangan){
      if(!isset(${"idMaster" . $vruangan['Id']})){
         ${"idMaster" . $vruangan['Id']} = 'DAMR'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterRuangan = "INSERT INTO `detail_acara_master_ruangan`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateRuangan`, `IdRuangan`, `MaxKapasitas`, `Biaya`, `Catatan`, `RangeDay`, `LevelStatus`) 
               VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateRuangan, :IdRuangan, :MaxKapasitas, :Biaya, :Catatan, :RangeDay, :LevelStatus)";
      $resultDetailMasterRuangan = coreNoReturn($sqlDetailMasterRuangan, array( 
                                          ":Id" => ${"idMaster" . $vruangan['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vruangan['IdSubweb'],
                                          ":IdTemplateRuangan" => $vruangan['IdTemplateRuangan'],
                                          ":IdRuangan" => $vruangan['IdRuangan'],
                                          ":MaxKapasitas" => $vruangan['MaxKapasitas'],
                                          ":Biaya" => $vruangan['Biaya'],
                                          ":Catatan" => $vruangan['Catatan'],
                                          ":RangeDay" => $vruangan['RangeDay'],
                                          ":LevelStatus" => $vruangan['LevelStatus']
                                       ));
      if($resultDetailMasterRuangan['success'] == 1){
         $xMasterRuangan++;
      } else {
         error_log("INSERT detail_acara_master_ruangan " . $resultDetailMasterRuangan['message'], 0);
      }
   }

   if ($xMasterRuangan !== sizeof($rsqlMRuangan)) {
      $status .= 'gagal insert detail acara master ruangan. '; 
   }

   return $status;
}

function detailAcaraPetugas($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplatePetugas = 0;
   $sqlTPetugas = "SELECT * FROM detail_acara_template_petugas WHERE IdAcara = :IdAcara";
   $rsqlTPetugas = coreReturnArray($sqlTPetugas, array(":IdAcara" => $idAcara));
   foreach($rsqlTPetugas as $key => $vPetugas){
      if(!isset(${"idTemplate" . $vPetugas['Id']})){
         ${"idTemplate" . $vPetugas['Id']} = 'DATP'. date("ymdHis") . $key . $index;
      }

      $sqlDetailTemplatePetugas = "INSERT INTO detail_acara_template_petugas(`Id`, `IdAcara`, `IdSubweb`, `IdTemplatePetugas`, `Catatan`, `RangeDay`, `LevelStatus`) 
                              VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplatePetugas, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplatePetugas, array(
                                                   ":Id" => ${"idTemplate" . $vPetugas['Id']}, 
                                                   ":IdAcara" => $idAcaraNew, 
                                                   ":IdSubweb" => $vPetugas['IdSubweb'],
                                                   ":IdTemplatePetugas" => $vPetugas['IdTemplatePetugas'],
                                                   ":Catatan" => $vPetugas['Catatan'],
                                                   ":RangeDay" => $vPetugas['RangeDay'],
                                                   ":LevelStatus" => $vPetugas['LevelStatus']
                                             ));
      if($result['success'] == 1){
          $xTemplatePetugas++;
      } else {
         error_log("INSERT detail_acara_template_petugas " . $result['message'], 0);
      }
   }

   if ($xTemplatePetugas !== sizeof($rsqlTPetugas)) {
      $status .= 'gagal insert detail acara template petugas. '; 
   }

   $xMasterPetugas = 0;
   $sqlMPetugas = "SELECT * FROM detail_acara_master_petugas WHERE IdAcara = :IdAcara";
   $rsqlMPetugas = coreReturnArray($sqlMPetugas, array(":IdAcara" => $idAcara));
   foreach($rsqlMPetugas as $key => $vPetugas){
      if(!isset(${"idMaster" . $vPetugas['Id']})){
         ${"idMaster" . $vPetugas['Id']} = 'DAMP'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterPetugas = "INSERT INTO `detail_acara_master_petugas`(`Id`, `IdAcara`, `IdSubweb`, `IdDetailTemplate`, `IdTemplatePetugas`, `IdPetugas`, `Jumlah`, `Type`, `Biaya`, `Catatan`, `RangeDay`, `LevelStatus`) 
               VALUES (:Id, :IdAcara, :IdSubweb, :IdDetailTemplate, :IdTemplatePetugas, :IdPetugas, :Jumlah, :Type, :Biaya, :Catatan, :RangeDay, :LevelStatus)";
      $resultDetailMasterPetugas = coreNoReturn($sqlDetailMasterPetugas, array( 
                                          ":Id" => ${"idMaster" . $vPetugas['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vPetugas['IdSubweb'],
                                          ":IdDetailTemplate" => $vPetugas['IdDetailTemplate'],
                                          ":IdTemplatePetugas" => $vPetugas['IdTemplatePetugas'],
                                          ":IdPetugas" => $vPetugas['IdPetugas'],
                                          ":Jumlah" => (int) $vPetugas['Jumlah'],
                                          ":Type" => $vPetugas['Type'],
                                          ":Biaya" => (int) $vPetugas['Biaya'],
                                          ":Catatan" => $vPetugas['Catatan'],
                                          ":RangeDay" => $vPetugas['RangeDay'],
                                          ":LevelStatus" => $vPetugas['LevelStatus']
                                       ));
      if($resultDetailMasterPetugas['success'] == 1){
         $xMasterPetugas++;
      } else {
         error_log("INSERT detail_acara_master_petugas " . $resultDetailMasterPetugas['message'], 0);
      }
   }

   if ($xMasterPetugas !== sizeof($rsqlMPetugas)) {
      $status .= 'gagal insert detail acara master petugas. '; 
   }

   return $status;
}

function detailAcaraNotif($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xMasterNotif = 0;
   $sqlMNotif = "SELECT * FROM detail_acara_master_notif WHERE IdAcara = :IdAcara";
   $rsqlMNotif = coreReturnArray($sqlMNotif, array(":IdAcara" => $idAcara));
   foreach($rsqlMNotif as $key => $vNotif){
      if(!isset(${"idMaster" . $vNotif['Id']})){
         ${"idMaster" . $vNotif['Id']} = 'DAMN'. date("ymdHis") . $key . $index;
      }

      $sqlDetailNotif = "INSERT INTO `detail_acara_master_notif`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateNotif`, `IdTemplatePetugas`, `IdNotif`, `TipePengiriman`, `TipeNotifikasi`, `IdTipePenerima`, `RangeDay`, `LevelStatus`) 
               VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateNotif, :IdTemplatePetugas, :IdNotif, :TipePengiriman, :TipeNotifikasi, :IdTipePenerima, :RangeDay, :LevelStatus)";
      $resultDetailNotif = coreNoReturn($sqlDetailNotif, array(
                                          ":Id" => ${"idMaster" . $vNotif['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vNotif["IdSubweb"], 
                                          ":IdTemplateNotif" => $vNotif["IdTemplateNotif"],
                                          ":IdTemplatePetugas" => $vNotif["IdTemplatePetugas"],
                                          ":IdNotif" => $vNotif["IdNotif"], 
                                          ":TipePengiriman" => $vNotif["TipePengiriman"], 
                                          ":TipeNotifikasi" => $vNotif["TipeNotifikasi"], 
                                          ":IdTipePenerima" => $vNotif["IdTipePenerima"],
                                          ":RangeDay" => $vNotif["RangeDay"],
                                          ":LevelStatus" => $vNotif["LevelStatus"]
                                       ));
      if($resultDetailNotif['success'] == 1){
          $xMasterNotif++;
      } else {
         error_log("INSERT detail_acara_master_notif " . $resultDetailNotif['message'], 0);
      }
   }

   if ($xMasterNotif !== sizeof($rsqlMNotif)) {
      $status .= 'gagal insert detail acara master notif. '; 
   }

   $imageDir = $GLOBALS['API_URL'].'/assets/img/'.date("Y/m/d").'/';
   if (!file_exists('assets/img/'.date("Y/m/d").'/')) {
      mkdir('assets/img/'.date("Y/m/d").'/', 0777, true);
   }

   $xContentNotif = 0;
   $sqlCNotif = "SELECT * FROM detail_acara_content_notif WHERE IdAcara = :IdAcara";
   $rsqlCNotif = coreReturnArray($sqlCNotif, array(":IdAcara" => $idAcara));
   foreach($rsqlCNotif as $key => $vNotif){
      if(!isset(${"idContent" . $vNotif['Id']})){
         ${"idContent" . $vNotif['Id']} = 'DAMN'. date("ymdHis") . $key . $index;
      }

      $sqlContentNotif = "INSERT INTO `detail_acara_content_notif`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateNotif`, `IdPetugas`, `TipeNotifikasi`, `IdTemplatePetugas`, `JudulNotif`, `DetailNotif`, `RangeDay`, `LevelStatus`) 
               VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateNotif, :IdPetugas, :TipeNotifikasi, :IdTemplatePetugas, :JudulNotif, :DetailNotif, :RangeDay, :LevelStatus)";
      $rsqlContentNotif = coreNoReturn($sqlContentNotif, array(
                                          ":Id" => ${"idContent" . $vNotif['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vNotif['IdSubweb'],
                                          ":IdTemplateNotif" => $vNotif['IdTemplateNotif'], 
                                          ":IdTemplatePetugas" => $vNotif['IdTemplatePetugas'], 
                                          ":IdPetugas" => $vNotif['IdPetugas'],
                                          ":TipeNotifikasi" => $vNotif['TipeNotifikasi'],
                                          ":JudulNotif" => $vNotif['JudulNotif'],
                                          ":DetailNotif" => $vNotif['DetailNotif'],
                                          ":RangeDay" => $vNotif['RangeDay'],
                                          ":LevelStatus" => $vNotif["LevelStatus"]
                                       ));
      if($rsqlContentNotif['success'] == 1){
          $xContentNotif++;
      } else {
         error_log("INSERT detail_acara_content_notif " . $rsqlContentNotif['message'], 0);
      }

      // select dokumen
      $sqlDokumen = "SELECT * FROM dokumen_master WHERE IdMaster = :IdContentNotif";
      $rsqlDokumen = coreReturnArray($sqlDokumen, array(":IdContentNotif" => $vNotif['Id']));

      foreach ($rsqlDokumen as $kDokumen => $vDokumen) {
         $sqlDokumenMaster = "INSERT INTO `dokumen_master`(`IdMaster`, `files`, `Thumbnail`) VALUES (:IdMaster, :File, :Thumbnail)";
         $newFilesNotif = str_replace($vNotif['Id'], ${"idContent" . $vNotif['Id']}, $vDokumen['files']);
         $rsqlDokumenMaster = coreNoReturn($sqlDokumenMaster, array(
                                                         ":IdMaster" => ${"idContent" . $vNotif['Id']}, 
                                                         ":File" => $newFilesNotif, 
                                                         ":Thumbnail" => $vDokumen['Thumbnail'],
                                                   ));

         $dirFile = str_replace(
            '//'.$_SERVER['HTTP_HOST'] . DIR_API_PRO . '/',
            '',
            $vDokumen['files']
         );
         $dirFileNew = str_replace($vDokumen['Id'], ${"idContent" . $vNotif['Id']}, $dirFile);

         // rename and copy file
         if (!copy($dirFile, $dirFileNew)) {
               $status .= "gagal copy dokumen content notif ";
         }
      }
   }

   if ($xContentNotif !== sizeof($rsqlCNotif)) {
      $status .= 'gagal insert detail acara master notif. '; 
   }
   
   return $status;
}

function detailAcaraKonsumsi($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplateKonsumsi = 0;
   $sqlTKonsumsi = "SELECT * FROM detail_acara_template_konsumsi WHERE IdAcara = :IdAcara";
   $rsqlTKonsumsi = coreReturnArray($sqlTKonsumsi, array(":IdAcara" => $idAcara));
   foreach($rsqlTKonsumsi as $key => $vkonsumsi){
      if(!isset(${"idTemplate" . $vkonsumsi['Id']})){
         ${"idTemplate" . $vkonsumsi['Id']} = 'DATK'. date("ymdHis"). $key . $index;
      }

      $sqlDetailTemplateKonsumsi = "INSERT INTO detail_acara_template_konsumsi(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateKonsumsi`, `NameTemplate`, `Catatan`, `RangeDay`, `LevelStatus`) 
                                 VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateKonsumsi, :NameTemplate, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplateKonsumsi, array( 
                                          ":Id" => ${"idTemplate" . $vkonsumsi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vkonsumsi['IdSubweb'],
                                          ":IdTemplateKonsumsi" => $vkonsumsi['IdTemplateKonsumsi'],
                                          ":NameTemplate" => $vkonsumsi['NameTemplate'],
                                          ":Catatan" => $vkonsumsi['Catatan'],
                                          ":RangeDay" => $vkonsumsi['RangeDay'],
                                          ":LevelStatus" => $vkonsumsi['LevelStatus']
                                       ));
                                       
      if($result['success'] == 1){
          $xTemplateKonsumsi++;
      } else {
         error_log("INSERT detail_acara_template_konsumsi " . $result['message'], 0);
      }
   }

   if ($xTemplateKonsumsi !== sizeof($rsqlTKonsumsi)) {
      $status .= 'gagal insert detail acara template konsumsi. '; 
   }

   $xMasterKonsumsi = 0;
   $sqlMKonsumsi = "SELECT * FROM detail_acara_master_konsumsi WHERE IdAcara = :IdAcara";
   $rsqlMKonsumsi = coreReturnArray($sqlMKonsumsi, array(":IdAcara" => $idAcara));
   foreach($rsqlMKonsumsi as $key => $vkonsumsi){
      if(!isset(${"idMaster" . $vkonsumsi['Id']})){
         ${"idMaster" . $vkonsumsi['Id']} = 'DAMK'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterKonsumsi = "INSERT INTO `detail_acara_master_konsumsi`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateKonsumsi`, `IdMakanan`, `IdAlatMakan`, `Porsi`, `Jumlah`, `Catatan`, `RangeDay`, `LevelStatus`) 
                           VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateKonsumsi, :IdMakanan, :IdAlatMakan, :Porsi, :Jumlah, :Catatan, :RangeDay, :LevelStatus)";
      $resultDetailMasterKonsumsi = coreNoReturn($sqlDetailMasterKonsumsi, array( 
                                          ":Id" => ${"idMaster" . $vkonsumsi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vkonsumsi['IdSubweb'],
                                          ":IdTemplateKonsumsi" => $vkonsumsi['IdTemplateKonsumsi'],
                                          ":IdMakanan" => $vkonsumsi['IdMakanan'],
                                          ":IdAlatMakan" => $vkonsumsi['IdAlatMakan'],
                                          ":Porsi" => $vkonsumsi['Porsi'],
                                          ":Jumlah" => (int) $vkonsumsi['Jumlah'],
                                          ":Catatan" => $vkonsumsi['Catatan'],
                                          ":RangeDay" => $vkonsumsi['RangeDay'],
                                          ":LevelStatus" => $vkonsumsi['LevelStatus']
                                       ));
      if($resultDetailMasterKonsumsi['success'] == 1){
         $xMasterKonsumsi++;
      } else {
         error_log("INSERT detail_acara_master_konsumsi " . $resultDetailMasterKonsumsi['message'], 0);
      }
   }

   if ($xMasterKonsumsi !== sizeof($rsqlMKonsumsi)) {
      $status .= 'gagal insert detail acara master konsumsi. '; 
   }

   return $status;
}

function detailAcaraDekorasi($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplateDekorasi = 0;
   $sqlTDekorasi = "SELECT * FROM detail_acara_template_dekorasi WHERE IdAcara = :IdAcara";
   $rsqlTDekorasi = coreReturnArray($sqlTDekorasi, array(":IdAcara" => $idAcara));
   foreach($rsqlTDekorasi as $key => $vdekorasi){
      if(!isset(${"idTemplate" . $vdekorasi['Id']})){
         ${"idTemplate" . $vdekorasi['Id']} = 'DATR'. date("ymdHis") . $key . $index;
      }

      $sqlDetailTemplateDekorasi = "INSERT INTO detail_acara_template_dekorasi(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateDekorasi`, `NameTemplate`, `Catatan`, `RangeDay`, `LevelStatus`) 
                                       VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateDekorasi, :NameTemplate, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplateDekorasi, array( 
                                             ":Id" => ${"idTemplate" . $vdekorasi['Id']}, 
                                             ":IdAcara" => $idAcaraNew, 
                                             ":IdSubweb" => (int) $vdekorasi['IdSubweb'],
                                             ":IdTemplateDekorasi" => $vdekorasi['IdTemplateDekorasi'],
                                             ":NameTemplate" => $vdekorasi['NameTemplate'],
                                             ":Catatan" => $vdekorasi['Catatan'],
                                             ":RangeDay" => $vdekorasi['RangeDay'],
                                             ":LevelStatus" => $vdekorasi['LevelStatus']
                                          ));
                                       
      if($result['success'] == 1){
          $xTemplateDekorasi++;
      } else {
         error_log("INSERT detail_acara_template_dekorasi " . $result['message'], 0);
      }
   }

   if ($xTemplateDekorasi !== sizeof($rsqlTDekorasi)) {
      $status .= 'gagal insert detail acara template dekorasi. '; 
   }

   $xMasterDekorasi = 0;
   $sqlMDekorasi = "SELECT * FROM detail_acara_master_dekorasi WHERE IdAcara = :IdAcara";
   $rsqlMDekorasi = coreReturnArray($sqlMDekorasi, array(":IdAcara" => $idAcara));
   foreach($rsqlMDekorasi as $key => $vdekorasi){
      if(!isset(${"idMaster" . $vdekorasi['Id']})){
         ${"idMaster" . $vdekorasi['Id']} = 'DATR'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterDekorasi = "INSERT INTO `detail_acara_master_dekorasi`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateDekorasi`, `IdDekorasi`, `Jumlah`, `Type`, `Biaya`, `RangeDay`, `Catatan`, `LevelStatus`) 
                           VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateDekorasi, :IdDekorasi, :Jumlah, :Type, :Biaya, :RangeDay, :Catatan, :LevelStatus)";
      $resultDetailMasterDekorasi = coreNoReturn($sqlDetailMasterDekorasi, array( 
                                          ":Id" => ${"idMaster" . $vdekorasi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vdekorasi['IdSubweb'],
                                          ":IdTemplateDekorasi" => $vdekorasi['IdTemplateDekorasi'],
                                          ":IdDekorasi" => $vdekorasi['IdDekorasi'],
                                          ":Jumlah" => $vdekorasi['Jumlah'],
                                          ":Type" => $vdekorasi['Type'],
                                          ":Biaya" => (int) $vdekorasi['Biaya'],
                                          ":RangeDay" => $vdekorasi['RangeDay'],
                                          ":Catatan" => $vdekorasi['Catatan'],
                                          ":LevelStatus" => $vdekorasi['LevelStatus']
                                       ));
      if($resultDetailMasterDekorasi['success'] == 1){
         $xMasterDekorasi++;
      } else {
         error_log("INSERT detail_acara_master_dekorasi " . $resultDetailMasterDekorasi['message'], 0);
      }
   }

   if ($xMasterDekorasi !== sizeof($rsqlMDekorasi)) {
      $status .= 'gagal insert detail acara master dekorasi. '; 
   }

   return $status;
}

function detailAcaraPerlengkapan($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplatePerlengkapan = 0;
   $sqlTPerlengkapan = "SELECT * FROM detail_acara_template_perlengkapan WHERE IdAcara = :IdAcara";
   $rsqlTPerlengkapan = coreReturnArray($sqlTPerlengkapan, array(":IdAcara" => $idAcara));
   foreach($rsqlTPerlengkapan as $key => $vperlengkapan){
      if(!isset(${"idTemplate" . $vperlengkapan['Id']})){
         ${"idTemplate" . $vperlengkapan['Id']} = 'DATP'. date("ymdHis") . $key . $index;
      }

      $sqlDetailTemplatePerlengkapan = "INSERT INTO detail_acara_template_perlengkapan(`Id`, `IdAcara`, `IdSubweb`, `IdTemplatePerlengkapan`, `NamaTemplate`, `Catatan`, `RangeDay`, `LevelStatus`) 
                                    VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplatePerlengkapan, :NamaTemplate, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplatePerlengkapan, array( 
                                             ":Id" => ${"idTemplate" . $vperlengkapan['Id']}, 
                                             ":IdAcara" => $idAcaraNew, 
                                             ":IdSubweb" => $vperlengkapan['IdSubweb'],
                                             ":IdTemplatePerlengkapan" => $vperlengkapan['IdTemplatePerlengkapan'],
                                             ":NamaTemplate" => $vperlengkapan['NamaTemplate'],
                                             ":Catatan" => $vperlengkapan['Catatan'],
                                             ":RangeDay" => $vperlengkapan['RangeDay'],
                                             ":LevelStatus" => $vperlengkapan['LevelStatus']
                                          ));
                                       
      if($result['success'] == 1){
          $xTemplatePerlengkapan++;
      } else {
         error_log("INSERT detail_acara_template_perlengkapan " . $result['message'], 0);
      }
   }

   if ($xTemplatePerlengkapan !== sizeof($rsqlTPerlengkapan)) {
      $status .= 'gagal insert detail acara template perlengkapan. '; 
   }

   $xMasterPerlengkapan = 0;
   $sqlMPerlengkapan = "SELECT * FROM detail_acara_master_perlengkapan WHERE IdAcara = :IdAcara";
   $rsqlMPerlengkapan = coreReturnArray($sqlMPerlengkapan, array(":IdAcara" => $idAcara));
   foreach($rsqlMPerlengkapan as $key => $vperlengkapan){
      if(!isset(${"idMaster" . $vperlengkapan['Id']})){
         ${"idMaster" . $vperlengkapan['Id']} = 'DAMP'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterPerlengkapan = "INSERT INTO `detail_acara_master_perlengkapan`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplatePerlengkapan`, `IdAlat`, `IdBahan`, `Jumlah`, `Biaya`, `Catatan`, `RangeDay`, `LevelStatus`) 
                           VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplatePerlengkapan, :IdAlat, :IdBahan, :Jumlah, :Biaya, :Catatan, :RangeDay, :LevelStatus)";
      $resultDetailMasterPerlengkapan = coreNoReturn($sqlDetailMasterPerlengkapan, array( 
                                          ":Id" => ${"idMaster" . $vperlengkapan['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vperlengkapan['IdSubweb'],
                                          ":IdTemplatePerlengkapan" => $vperlengkapan['IdTemplatePerlengkapan'],
                                          ":IdAlat" => $vperlengkapan['IdAlat'],
                                          ":IdBahan" => $vperlengkapan['IdBahan'],
                                          ":Jumlah" => $vperlengkapan['Jumlah'],
                                          ":Biaya" => (int) $vperlengkapan['Biaya'],
                                          ":Catatan" => $vperlengkapan['Catatan'],
                                          ":RangeDay" => $vperlengkapan['RangeDay'],
                                          ":LevelStatus" => $vperlengkapan['LevelStatus']
                                       ));
      if($resultDetailMasterPerlengkapan['success'] == 1){
         $xMasterPerlengkapan++;
      } else {
         error_log("INSERT detail_acara_master_perlengkapan " . $resultDetailMasterPerlengkapan['message'], 0);
      }
   }

   if ($xMasterPerlengkapan !== sizeof($rsqlMPerlengkapan)) {
      $status .= 'gagal insert detail acara master perlengkapan. '; 
   }

   return $status;
}

function detailAcaraTransportasi($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplateTransportasi = 0;
   $sqlTTransportasi = "SELECT * FROM detail_acara_template_transportasi WHERE IdAcara = :IdAcara";
   $rsqlTTransportasi = coreReturnArray($sqlTTransportasi, array(":IdAcara" => $idAcara));
   foreach($rsqlTTransportasi as $key => $vtransportasi){
      if(!isset(${"idTemplate" . $vtransportasi['Id']})){
         ${"idTemplate" . $vtransportasi['Id']} = 'DATT'. date("ymdHis") . $key . $index;
      }

      $sqlDetailTemplateTransportasi = "INSERT INTO detail_acara_template_transportasi(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateTransportasi`, `NamaTemplate`, `Catatan`, `RangeDay`, `LevelStatus`) 
                                 VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateTransportasi, :NamaTemplate, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplateTransportasi, array( 
                                          ":Id" => ${"idTemplate" . $vtransportasi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vtransportasi['IdSubweb'],
                                          ":IdTemplateTransportasi" => $vtransportasi['IdTemplateTransportasi'],
                                          ":NamaTemplate" => $vtransportasi['NamaTemplate'],
                                          ":Catatan" => $vtransportasi['Catatan'],
                                          ":RangeDay" => $vtransportasi['RangeDay'],
                                          ":LevelStatus" => $vtransportasi['LevelStatus']
                                       ));
      if($result['success'] == 1){
          $xTemplateTransportasi++;
      } else {
         error_log("INSERT detail_acara_template_transportasi " . $result['message'], 0);
      }
   }

   if ($xTemplateTransportasi !== sizeof($rsqlTTransportasi)) {
      $status .= 'gagal insert detail acara template transportasi. '; 
   }

   $xMasterTransportasi = 0;
   $sqlMTransportasi = "SELECT * FROM detail_acara_master_transportasi WHERE IdAcara = :IdAcara";
   $rsqlMTransportasi = coreReturnArray($sqlMTransportasi, array(":IdAcara" => $idAcara));
   foreach($rsqlMTransportasi as $key => $vtransportasi){
      if(!isset(${"idMaster" . $vtransportasi['Id']})){
         ${"idMaster" . $vtransportasi['Id']} = 'DAMT'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterTransportasi = "INSERT INTO `detail_acara_master_transportasi`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateTransportasi`, `IdKendaraan`,`Jumlah`, `Biaya`, `Catatan`, `RangeDay`, `LevelStatus`) 
                           VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateTransportasi, :IdKendaraan, :Jumlah, :Biaya, :Catatan,:RangeDay, :LevelStatus)";
      $resultDetailMasterTransportasi = coreNoReturn($sqlDetailMasterTransportasi, array( 
                                          ":Id" => ${"idMaster" . $vtransportasi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vtransportasi['IdSubweb'],
                                          ":IdTemplateTransportasi" => $vtransportasi['IdTemplateTransportasi'],
                                          ":IdKendaraan" => $vtransportasi['IdKendaraan'],
                                          ":Jumlah" => $vtransportasi['Jumlah'],
                                          ":Biaya" => (int) $vtransportasi['Biaya'],
                                          ":Catatan" => $vtransportasi['Catatan'],
                                          ":RangeDay" => $vtransportasi['RangeDay'],
                                          ":LevelStatus" => $vtransportasi['LevelStatus']
                                       ));
      if($resultDetailMasterTransportasi['success'] == 1){
         $xMasterTransportasi++;
      } else {
         error_log("INSERT detail_acara_master_transportasi " . $resultDetailMasterTransportasi['message'], 0);
      }
   }

   if ($xMasterTransportasi !== sizeof($rsqlMTransportasi)) {
      $status .= 'gagal insert detail acara master transportasi. '; 
   }

   return $status;
}

function detailAcaraKomunikasi($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplateKomunikasi = 0;
   $sqlTKomunikasi = "SELECT * FROM detail_acara_template_komunikasi WHERE IdAcara = :IdAcara";
   $rsqlTKomunikasi = coreReturnArray($sqlTKomunikasi, array(":IdAcara" => $idAcara));
   foreach($rsqlTKomunikasi as $key => $vkomunikasi){
      if(!isset(${"idTemplate" . $vkomunikasi['Id']})){
         ${"idTemplate" . $vkomunikasi['Id']} = 'DATKOM'. date("ymdHis") . $key . $index;
      }

      $sqlDetailTemplateKomunikasi = "INSERT INTO detail_acara_template_komunikasi(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateKomunikasi`, `NameTemplate`, `Catatan`, `RangeDay`, `LevelStatus`) 
                                       VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateKomunikasi, :NameTemplate, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplateKomunikasi, array( 
                                             ":Id" => ${"idTemplate" . $vkomunikasi['Id']}, 
                                             ":IdAcara" => $idAcaraNew, 
                                             ":IdSubweb" => $vkomunikasi['IdSubweb'],
                                             ":IdTemplateKomunikasi" => $vkomunikasi['IdTemplateKomunikasi'],
                                             ":NameTemplate" => $vkomunikasi['NameTemplate'],
                                             ":Catatan" => $vkomunikasi['Catatan'],
                                             ":RangeDay" => $vkomunikasi['RangeDay'],
                                             ":LevelStatus" => $vkomunikasi['LevelStatus']
                                          ));
      if($result['success'] == 1){
          $xTemplateKomunikasi++;
      } else {
         error_log("INSERT detail_acara_template_komunikasi " . $result['message'], 0);
      }
   }

   if ($xTemplateKomunikasi !== sizeof($rsqlTKomunikasi)) {
      $status .= 'gagal insert detail acara template komunikasi. '; 
   }

   $xMasterKomunikasi = 0;
   $sqlMKomunikasi = "SELECT * FROM detail_acara_master_komunikasi WHERE IdAcara = :IdAcara";
   $rsqlMKomunikasi = coreReturnArray($sqlMKomunikasi, array(":IdAcara" => $idAcara));
   foreach($rsqlMKomunikasi as $key => $vkomunikasi){
      if(!isset(${"idMaster" . $vkomunikasi['Id']})){
         ${"idMaster" . $vkomunikasi['Id']} = 'DAMKOM'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterKomunikasi = "INSERT INTO `detail_acara_master_komunikasi`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateKomunikasi`, `IdDetailKomunikasi`, `IdKomunikasi`, `Ukuran`, `Satuan`, `Jumlah`, `Catatan`, `RangeDay`, `LevelStatus`) 
                           VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateKomunikasi, :IdDetailKomunikasi, :IdKomunikasi, :Ukuran, :Satuan, :Jumlah, :Catatan, :RangeDay, :LevelStatus)";
      $resultDetailMasterKomunikasi = coreNoReturn($sqlDetailMasterKomunikasi, array( 
                                          ":Id" => ${"idMaster" . $vkomunikasi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vkomunikasi['IdSubweb'],
                                          ":IdTemplateKomunikasi" => $vkomunikasi['IdTemplateKomunikasi'],
                                          ":IdDetailKomunikasi" => $vkomunikasi['IdDetailKomunikasi'],
                                          ":IdKomunikasi" => $vkomunikasi['IdKomunikasi'],
                                          ":Ukuran" => $vkomunikasi['Ukuran'],
                                          ":Satuan" => $vkomunikasi['Satuan'],
                                          ":Jumlah" => $vkomunikasi['Jumlah'],
                                          ":Catatan" => $vkomunikasi['Catatan'],
                                          ":RangeDay" => $vkomunikasi['RangeDay'],
                                          ":LevelStatus" => $vkomunikasi['LevelStatus']
                                       ));
      if($resultDetailMasterKomunikasi['success'] == 1){
         $xMasterKomunikasi++;
      } else {
         error_log("INSERT detail_acara_master_komunikasi " . $resultDetailMasterKomunikasi['message'], 0);
      }
   }

   if ($xMasterKomunikasi !== sizeof($rsqlMKomunikasi)) {
      $status .= 'gagal insert detail acara master komunikasi. '; 
   }

   return $status;
}

function detailAcaraDokumentasi($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xTemplateDokumentasi = 0;
   $sqlTDokumentasi = "SELECT * FROM detail_acara_template_dokumentasi WHERE IdAcara = :IdAcara";
   $rsqlTDokumentasi = coreReturnArray($sqlTDokumentasi, array(":IdAcara" => $idAcara));
   foreach($rsqlTDokumentasi as $key => $vdokumentasi){
      if(!isset(${"idTemplate" . $vdokumentasi['Id']})){
         ${"idTemplate" . $vdokumentasi['Id']} = 'DATDO'. date("ymdHis") . $key . $index;
      }

      $sqlDetailTemplateDokumentasi = "INSERT INTO detail_acara_template_dokumentasi(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateDokumentasi`, `NamaTemplate`, `Catatan`, `RangeDay`, `LevelStatus`) 
                                       VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateDokumentasi, :NamaTemplate, :Catatan, :RangeDay, :LevelStatus)";
      $result = coreNoReturn($sqlDetailTemplateDokumentasi, array( 
                                          ":Id" => ${"idTemplate" . $vdokumentasi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vdokumentasi['IdSubweb'],
                                          ":IdTemplateDokumentasi" => $vdokumentasi['IdTemplateDokumentasi'],
                                          ":NamaTemplate" => $vdokumentasi['NamaTemplate'],
                                          ":Catatan" => $vdokumentasi['Catatan'],
                                          ":RangeDay" => $vdokumentasi['RangeDay'],
                                          ":LevelStatus" => $vdokumentasi['LevelStatus']
                                       ));
      if($result['success'] == 1){
          $xTemplateDokumentasi++;
      } else {
         error_log("INSERT detail_acara_template_dokumentasi " . $result['message'], 0);
      }
   }

   if ($xTemplateDokumentasi !== sizeof($rsqlTDokumentasi)) {
      $status .= 'gagal insert detail acara template dokumentasi. '; 
   }

   $xMasterDokumentasi = 0;
   $sqlMDokumentasi = "SELECT * FROM detail_acara_master_dokumentasi WHERE IdAcara = :IdAcara";
   $rsqlMDokumentasi = coreReturnArray($sqlMDokumentasi, array(":IdAcara" => $idAcara));
   foreach($rsqlMDokumentasi as $key => $vdokumentasi){
      if(!isset(${"idMaster" . $vdokumentasi['Id']})){
         ${"idMaster" . $vdokumentasi['Id']} = 'DAMDO'. date("ymdHis") . $key . $index;
      }

      $sqlDetailMasterDokumentasi = "INSERT INTO `detail_acara_master_dokumentasi`(`Id`, `IdAcara`, `IdSubweb`, `IdTemplateDokumentasi`, `IdDokumentasi`,`Catatan`,`RangeDay`, `LevelStatus`) 
                           VALUES (:Id, :IdAcara, :IdSubweb, :IdTemplateDokumentasi, :IdDokumentasi, :Catatan, :RangeDay, :LevelStatus)";
      $resultDetailMasterDokumentasi = coreNoReturn($sqlDetailMasterDokumentasi, array( 
                                          ":Id" => ${"idMaster" . $vdokumentasi['Id']}, 
                                          ":IdAcara" => $idAcaraNew, 
                                          ":IdSubweb" => $vdokumentasi['IdSubweb'],
                                          ":IdTemplateDokumentasi" => $vdokumentasi['IdTemplateDokumentasi'],
                                          ":IdDokumentasi" => $vdokumentasi['IdDokumentasi'],
                                          ":Catatan" => $vdokumentasi['Catatan'],
                                          ":RangeDay" => $vdokumentasi['RangeDay'],
                                          ":LevelStatus" => $vdokumentasi['LevelStatus']
                                       ));
      if($resultDetailMasterDokumentasi['success'] == 1){
         $xMasterDokumentasi++;
      } else{
         error_log("INSERT detail_acara_master_dokumentasi " . $resultDetailMasterDokumentasi['message'], 0);
      }
   }

   if ($xMasterDokumentasi !== sizeof($rsqlMDokumentasi)) {
      $status .= 'gagal insert detail acara master dokumentasi. '; 
   }

   return $status;
}

function statusAcara($levelStatus){
   if($levelStatus == '1'){
      return 'Perencanaan';
   }
   if($levelStatus == '2'){
      return 'Persetujuan';
   }
   if($levelStatus == '3'){
      return 'Persiapan';
   }
   if($levelStatus == '4'){
      return 'Pelaksanaan';
   }
   if($levelStatus == '5'){
      return 'Selesai';
   }

   return '';
}

function insertLaporanAcara($idAcara, $idAcaraNew, $index, $levelStatus){
   $status = '';
   $xIsi = 0;
   $sqlIsiLaporan = "SELECT * FROM mt_isi_laporan_acara WHERE IdAcara = :IdAcara";
   $rsqlIsiLaporan = coreReturnArray($sqlIsiLaporan, array(":IdAcara" => $idAcara));

   foreach ($rsqlIsiLaporan as $key => $value) {
      $sqlIsi = "INSERT INTO mt_isi_laporan_acara(IdAcara, Isi, Orders, LevelStatus) 
         VALUES(:acara, :isi, :order, :ls)
         ON DUPLICATE KEY 
         UPDATE `Isi`= :isi2";
      $rIsi = coreNoReturn($sqlIsi, array(":isi"=>$value['Isi'], ":isi2"=>$value['Isi'], ":order"=>$value['Orders'], ":acara"=>$idAcaraNew, ":ls"=>$value['LevelStatus']));   
      if ($rIsi['success'] == 1) {
         $xIsi++;
      } else {
         error_log("INSERT mt_isi_laporan_acara " . $rIsi['message'], 0);
      }
   }

   if($xIsi !== sizeof($rsqlIsiLaporan)){
      $status = 'gagal insert isi laporan';
   }

   $xPenerima = 0;
   $sqlPenerimaLaporan = "SELECT * FROM penerima_laporan_acara WHERE IdAcara = :IdAcara";
   $rsqlPenerimaLaporan = coreReturnArray($sqlPenerimaLaporan, array(":IdAcara" => $idAcara));

   foreach ($rsqlPenerimaLaporan as $key => $value) {
      $sqlPenerima = "INSERT INTO penerima_laporan_acara(Id, IdAcara, IdUser, LevelStatus) 
            VALUES(:id, :acara, :user, :ls)
            ON DUPLICATE KEY 
            UPDATE `IdUser`= :user2";
      $rsqlPenerima = coreNoReturn($sqlPenerima, array(":user"=>$value['IdUser'], ":user2"=>$value['IdUser'], ":id"=>$value['Id'], ":acara"=>$idAcaraNew, ":ls"=>$value['LevelStatus']));   
      if ($rIsi['success'] == 1) {
         $xPenerima++;
      } else {
         error_log("INSERT penerima_laporan_acara " . $rIsi['message'], 0);
      }
   }

   if($xPenerima !== sizeof($rsqlPenerimaLaporan)){
      $status = 'gagal insert penerima laporan';
   }

   return $status;
}

function addLogAcaraSchedule($idAcara, $idAcaraNew, $index, $levelStatus, $idUser){
   $status = '';
   $xlog = 0;
   $sqlLog = "SELECT * FROM log_acara WHERE IdAcara = :IdAcara ORDER BY Id DESC";
   $rsqlLog = coreReturnArray($sqlLog, array(":IdAcara" => $idAcara));

   foreach ($rsqlLog as $key => $value) {
      $sqlAddLogAcara = "INSERT INTO log_acara(`IdAcara`, `Status`, `Catatan`, `CreatedBy`) 
                        VALUES (:IdAcara, :Status, :Catatan, :CreatedBy)";
      $result = coreNoReturn($sqlAddLogAcara, array(
                           ":IdAcara" => $idAcaraNew, 
                           ":Status" => $value['Status'],
                           ":Catatan" => $value['Catatan'],
                           ":CreatedBy" => $idUser
                        ));
      if($result['success'] == 1 ) {
         $xlog++;
      } else {
         error_log("INSERT log_acara " . $result['message'], 0);
      }
   }

   if($xlog !== sizeof($rsqlLog)){
      $status = 'gagal insert log acara';
   }

   return $status;
}

function notifReassignTask($idAcara, $typeTemplateReassign){
   // notif reassign task ke koordinator bagian
   $sqlKoordinator = "SELECT u.Username, u.Email, u.NoTlp
                           FROM (
                               SELECT 
                                   IdUser
                               FROM 
                                   `koordinator_bagian_acara` 
                               WHERE 
                                   IdAcara = :IdAcara AND
                                   TypeTemplate = CONCAT(
                                       'Template ', 
                                       UCASE(LEFT(:TypeTemplateReassign, 1)), 
                                       LCASE(SUBSTRING(:TypeTemplateReassign2, 2))
                                   )
                           ) kba
                           INNER JOIN user u
                           ON u.Id = kba.IdUser";
   $rKoordinator = coreReturnArray($sqlKoordinator, array(
                                   ":IdAcara" => $idAcara, 
                                   "TypeTemplateReassign" => $typeTemplateReassign,
                                   "TypeTemplateReassign2" => $typeTemplateReassign
                               ));

   foreach($rKoordinator as $key => $vKoordinator){
       $sqlAcara = "SELECT NamaAcara, IdSubweb FROM acara WHERE Id = :IdAcara LIMIT 1";
       $rAcara = coreReturnArray($sqlAcara, array(":IdAcara" => $idAcara));
       $namaAcara = '';
       $idSubweb = '';
       if(sizeof($rAcara) > 0){
           $namaAcara = $rAcara[0]['NamaAcara'];
           $idSubweb = $rAcara[0]['IdSubweb'];
       }

       // notif browser
       $subject = "Atur Ulang Penugasan";
       $link = $_SERVER['SERVER_NAME'].$GLOBALS['globalVar']['PATH_FRONTEND'].'user/admin/task-list/'.$typeTemplateReassign."?idsubweb=".$idSubweb;
       $linkBrowser = $GLOBALS['globalVar']['PATH_FRONTEND'].'user/admin/task-list/'.$typeTemplateReassign."?idsubweb=".$idSubweb;
       $message = "Koordinator acara telah meminta atur ulang penugasan task ".$typeTemplateReassign." di acara ".$namaAcara.", segera perbaharui detail penugasan task.";
       $messageURL  = $message ." \n\nPerbaharui langsung melalui link dibawah 👇 \n".$link;
       $kirimId = array();

       $sql = "SELECT * FROM user_notification WHERE user_name=:username ORDER BY created_at DESC LIMIT 3";
       $result2 = coreReturnArray($sql, array(":username" => $vKoordinator['Username']));
       foreach ($result2 as $data2) {
           if($data2['push_notification_id'] != 'null' && $data2['push_notification_id'] != 'undefined'){
               array_push($kirimId,$data2['push_notification_id']);
           }
       }    
       $Browser = Notif22($kirimId, $subject, $linkBrowser, $message);
       $response['KirimNotif'] = $Browser;
       
       // notif email
       $Email = SendEmail(
           $subject, 
           $messageURL, 
           $vKoordinator['Email'], 
           "" );
       $response['KirimEmail'] = $Email;

       // notif wa
       $Whatsapp = sendUniversalNotifWa('text', $vKoordinator['NoTlp'], $messageURL);
       if($Whatsapp['Error'] == 1){
           error_log($vKoordinator['NoTlp'] .'Gagal UpdateLog', 0);
       }
   }

   return true;
}