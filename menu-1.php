<?php 
require_once ("inc/config.php");

function callCURLs($jsonData, $endpoint) {
    global $url;
    $ch = curl_init($url.$endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    curl_close($ch);

    vsdc_log($url . $endpoint, $jsonData, $response, $info, $errno, $error);

    return $response;
  }
  
  
  function sendStockMasters($data) {
    global $db;
      $return = array();
      foreach ($data as $dt) {
          $response = callCURLs($dt, 'items/saveItems');
          if (strlen($response) !== 0) {
              $return[] = $response;
          }
      }

      return $return;
  }






    $i = 0;
    $sql = $conn->prepare("SELECT * FROM `menu` where menu_id='806'");
    $sql->execute();
    while($fetch = $sql->fetch()){
        
    $jsonMaster[] = '
    {"itemNm":"'.$fetch['menu_name'].'",
 "tin":"'.$company_tin.'",
   "bhfId":"00",
    "itemStdNm":null,
    "btchNo":null,
    "bcd":null,
    "grpPrcL1":'.(int)$fetch['grpPrcL1'].',
    "grpPrcL2":'.(int)$fetch['grpPrcL2'].',
    "grpPrcL3":'.(int)$fetch['grpPrcL3'].',
    "grpPrcL4":'.(int)$fetch['grpPrcL4'].',
    "grpPrcL5":'.(int)$fetch['grpPrcL5'].',
    "addInfo":null,
    "sftyQty":null,
    "isrcAplcbYn":"'.$fetch['isrcAplcbYn'].'",
    "useYn":"'.$fetch['useYn'].'",
    "itemClsCd":"90101500",
    "regrNm":"'.$company_name.'",
    "regrId":"kbc",
    "modrNm":"'.$company_name.'",
    "modrId":"kbc",
    "qtyUnitCd":"'.$fetch['qtyUnitCd'].'",
    "itemTyCd":"'.$fetch['itemTyCd'].'",
    "taxTyCd":"'.$fetch['taxTyCd'].'",
    "pkgUnitCd":"'.$fetch['pkgUnitCd'].'",
    "orgnNatCd":"'.$fetch['orgnNatCd'].'",
    "dftPrc":'.(int)$fetch['dftPrc'].',
    "itemCd":"'.$fetch['itemCd'].'"
    }';
 

  }
print_r(sendStockMasters($jsonMaster));
//print_r($jsonMaster);









