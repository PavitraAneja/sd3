<?php
/********************************************************************
 * sync_property_huiting.php   ―  Incrementally sync Active statuses
 ********************************************************************/

date_default_timezone_set('America/Chicago');
echo "===== Property SYNC START  " . date('Y-m-d H:i:s') . " =====\n";

$FULL_REFRESH = false;   
$WINDOW_MIN   = 15;       
$PAGE_SIZE    = 200;     

require_once __DIR__ . '/db.php';
if ($conn->connect_errno) exit("DB error: {$conn->connect_error}\n");
echo "✅ DB connected.\n";

$row = $conn->query("
  SELECT access_token FROM token_store_huiting
   WHERE token_type='trestle'
ORDER BY expires_at DESC LIMIT 1
")->fetch_assoc();
if (!$row) exit("No token!\n");
$access_token = $row['access_token'];
echo "✅ Got token.\n";

$up = $conn->prepare("
INSERT INTO rets_property_huiting (
  L_ListingID, L_DisplayId, L_Status,
  L_SystemPrice, ModificationTimestamp,
  L_alldata, updated_date
) VALUES (?,?,?,?,?,?,NOW())
ON DUPLICATE KEY UPDATE
  L_DisplayId           = VALUES(L_DisplayId),
  L_Status              = VALUES(L_Status),
  L_SystemPrice         = VALUES(L_SystemPrice),
  ModificationTimestamp = VALUES(ModificationTimestamp),
  L_alldata             = VALUES(L_alldata),
  updated_date          = NOW()
");
$up->bind_param('ssssss',
  $L_ListingID,
  $L_DisplayId,
  $L_Status,
  $L_SystemPrice,
  $ModificationTimestamp,
  $L_alldata
);

$base = "https://api-trestle.corelogic.com/trestle/odata/Property";

$filterParts = [
    "PropertyType eq 'Residential'",
    "MlsStatus ne null"
];
if (!$FULL_REFRESH) {
    $since = gmdate('Y-m-d\\TH:i:s\\Z', strtotime("-{$WINDOW_MIN} minutes"));
    $filterParts[] = "ModificationTimestamp gt '$since'"; 
$filter = implode(' and ', $filterParts);

$params = [
    '$select' => 'ListingKeyNumeric,ListingId,MlsStatus,ListPrice,ModificationTimestamp', // ★
    '$orderby'=> 'ListingKeyNumeric desc',     
    '$filter' => $filter,
    '$top'    => $PAGE_SIZE,
    '$count'  => 'true'
];

$url = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

$page = 0;  $ins = 0;  $upd = 0;
while ($url) {
    echo "📤 Page {$page}\n";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$access_token}"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 40,
    ]);
    $json = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($json === false || $code !== 200) {
        $err = $json ?: curl_error($ch);
        exit("HTTP {$code}: {$err}\n");
    }
    curl_close($ch);

    $resp = json_decode($json);
    $rows = $resp->value ?? [];
    echo "📦  " . count($rows) . " rows\n";

    foreach ($rows as $row) {
        $L_ListingID           = $row->ListingKeyNumeric     ?? '';  // ★ 主键
        $L_DisplayId           = $row->ListingId             ?? '';
        $L_Status              = $row->MlsStatus             ?? '';
        $L_SystemPrice         = $row->ListPrice             ?? '';
        $ModificationTimestamp = $row->ModificationTimestamp ?? '';
        $L_alldata             = json_encode($row, JSON_UNESCAPED_UNICODE);

        $oldStatus = null;
        $res = $conn->query("
          SELECT L_Status FROM rets_property_huiting
           WHERE L_ListingID = '" . $conn->real_escape_string($L_ListingID) . "'
           LIMIT 1
        ");
        if ($res && $r = $res->fetch_row()) $oldStatus = $r[0];

        $up->execute();
        $aff = $up->affected_rows;

        if ($oldStatus === null && $aff >= 1) {
            $ins++;
        } elseif ($oldStatus !== null && $aff >= 1) {
            $upd++;
            if ($oldStatus !== $L_Status) {
                echo "🔄 {$L_ListingID}  {$oldStatus} → {$L_Status}\n";
            }
        }
    }

    $url  = $resp->{'@odata.nextLink'} ?? '';
    $page++;
}

echo "✅ Inserted {$ins} | 🔄 Updated {$upd}\n";
echo "===== Property SYNC END    " . date('Y-m-d H:i:s') . " =====\n";
