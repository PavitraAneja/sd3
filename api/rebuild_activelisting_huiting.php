<?php
/********************************************************************
 * rebuild_activelisting_huiting.php
 ********************************************************************/

ini_set('display_errors', 1);
error_reporting(E_ALL);

$mysqli = new mysqli('localhost', 'boxgra6_sd3', 'Real_estate123$', 'boxgra6_sd3');
if ($mysqli->connect_error) die('❌ DB connect error: '.$mysqli->connect_error);

$tableListings = 'activelistings';  
$batchSize     = 200;

$tok = $mysqli->query("
  SELECT access_token, expires_at
    FROM token_store_huiting
   WHERE token_type='trestle'
ORDER BY expires_at DESC LIMIT 1
")->fetch_assoc();

if (!$tok)             die("❌ No token — enerate_token.php\n");
if (time() > $tok['expires_at']) die("❌ Token expired — token\n");
$access = $tok['access_token'];

$mysqli->query("TRUNCATE {$tableListings}");
echo "🔄 TRUNCATE {$tableListings} done\n";

$cols = "
  L_ListingID,L_DisplayId,L_Address,L_Zip,LM_char10_70,L_AddressStreet,
  L_City,L_State,L_Class,L_Type_,L_Keyword2,LM_Dec_3,L_Keyword1,L_Keyword5,
  L_Keyword7,L_SystemPrice,LM_Int2_3,L_ListingDate,ListingContractDate,
  LMD_MP_Latitude,LMD_MP_Longitude,LA1_UserFirstName,LA1_UserLastName,
  L_Status,LO1_OrganizationName,L_Remarks,L_Photos,PhotoTime,PhotoCount,L_alldata";
$placeholders = rtrim(str_repeat('?,', 30), ',');
$ins = $mysqli->prepare("INSERT INTO {$tableListings} ($cols) VALUES ($placeholders)");
if (!$ins) die('❌ Prepare failed: '.$mysqli->error);

$skip  = 0;
$total = 0;

while (true) {
    $url = 'https://api-trestle.corelogic.com/trestle/odata/Property?' .
        http_build_query([
            '$orderby' => 'ListingContractDate desc,ListingKey desc',
            '$filter'  => "PropertyType eq 'Residential' and MlsStatus eq 'Active'",
            '$skip'    => $skip,
            '$top'     => $batchSize,
            '$count'   => 'true',
            '$expand'  => 'Media($orderby=Order)'
        ], '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$access}"],
        CURLOPT_TIMEOUT        => 60
    ]);
    $json = curl_exec($ch) ?: die('curl error: '.curl_error($ch));
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http !== 200) die("❌ API HTTP {$http}\n");

    $resp = json_decode($json);
    $rows = $resp->value ?? [];
    if (!$rows) break;         

    foreach ($rows as $r) {
        $media = [];
        foreach (($r->Media ?? []) as $m) $media[] = $m->MediaURL;

        $bind = [
          $r->ListingKey,
          $r->ListingId,
          $r->UnparsedAddress,
          $r->PostalCode,
          $r->SubdivisionName,
          $r->StreetName,
          $r->City,
          $r->StateOrProvince,
          $r->PropertyType,
          $r->PropertySubType,
          $r->BedroomsTotal,
          $r->BathroomsTotalInteger,
          $r->LotSizeArea,
          $r->GarageSpaces,
          $r->Levels ?? '',
          $r->ListPrice,
          $r->LivingArea,
          date('Y-m-d H:i:s', strtotime($r->ModificationTimestamp)),
          date('Y-m-d H:i:s', strtotime($r->ListingContractDate)),
          $r->Latitude,
          $r->Longitude,
          $r->ListAgentFirstName,
          $r->ListAgentLastName,
          $r->MlsStatus,
          $r->ListOfficeName,
          $r->PublicRemarks,
          json_encode($media),
          date('Y-m-d H:i:s', strtotime($r->PhotosChangeTimestamp)),
          $r->PhotosCount,
          json_encode($r, JSON_UNESCAPED_UNICODE)
        ];
        $ins->bind_param(str_repeat('s', 30), ...$bind);
        $ins->execute();
        $total++;
    }
    printf("✅ page skip=%d inserted %d rows\n", $skip, count($rows));
    $skip += $batchSize;
}

$ins->close();
$mysqli->close();
echo "🎉 FINISHED — total {$total} active rows written\n";
