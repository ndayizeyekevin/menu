<?php 
require_once ("inc/config.php");

function callCURL1($jsonData, $endpoint) {
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
  
  
  function sendStockMaster1($data) {
    global $db;
    global $conn; 
      $return = array();
      $output='';
      $count = 0;
      foreach ($data as $dt) {
        $count++;
          $response = callCURL1($dt, 'items/saveItems');
          if (strlen($response) !== 0) {
              $return[] = $response;
            if ($response) {
              $data = json_decode($response);
              $itmcd = json_decode($dt);
              $itmcd = $itmcd->itemCd;
              $code = $data->resultCd;
              if($code=='000'){
                // $sql = $conn->prepare("DELETE FROM `menu` WHERE itemCd='$itmcd'");
                // $sql->execute();
              }
            
              $output.=$response.$count."<br>";
          }
      }
    }

      return $output;
  }



    $i = 0;
    $sql = $conn->prepare("SELECT * FROM `menu` LIMIT 1");
    $sql->execute();
    while($fetch = $sql->fetch()){
        
    $jsonMaster[] = '
    {
    
    "tin":"'.$company_tin.'",
    "bhfId":"00",
    "itemCd":"'.$fetch['itemCd'].'",
    "itemClsCd":"90101500",
    "itemTyCd":"'.$fetch['itemTyCd'].'",
    "itemNm":"'.$fetch['menu_name'].'",
    "itemStdNm":null,
    "orgnNatCd":"'.$fetch['orgnNatCd'].'",
    "pkgUnitCd":"'.$fetch['pkgUnitCd'].'",
    "qtyUnitCd":"'.$fetch['qtyUnitCd'].'",
    "taxTyCd":"'.$fetch['taxTyCd'].'",
    "btchNo":null,
    "bcd":null,
    "dftPrc":'.(int)$fetch['dftPrc'].',
    "grpPrcL1":'.(int)$fetch['grpPrcL1'].',
    "grpPrcL2":'.(int)$fetch['grpPrcL2'].',
    "grpPrcL3":'.(int)$fetch['grpPrcL3'].',
    "grpPrcL4":'.(int)$fetch['grpPrcL4'].',
    "grpPrcL5":null,
    "addInfo":null,
    "sftyQty":null,
    "isrcAplcbYn":"'.$fetch['isrcAplcbYn'].'",
    "useYn":"Y",
    "regrNm":"'.$company_name.'",
    "regrId":"'.$company_name.'",
    "modrNm":"'.$company_name.'",
    "modrId":"'.$company_name.'"
    }';
 

  }


  // echo sendStockMaster1($jsonMaster);
print_r($jsonMaster);









