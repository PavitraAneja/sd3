<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Chicago');

echo "===== OpenHouse ACTIVE SYNC START " . date('Y-m-d H:i:s') . " =====\n";

require_once __DIR__ . '/db.php';
if ($conn->connect_errno) exit("❌ DB connect: ({$conn->connect_errno}) {$conn->connect_error}\n");
$conn->set_charset('utf8mb4');

list($dbName)   = $conn->query("SELECT DATABASE()")->fetch_row();
list($hostName) = $conn->query("SELECT @@hostname")->fetch_row();
echo "✅ DB: {$dbName} @ {$hostName}\n";

/* Remove existing rows - keep only current/future active rows */
if (!$conn->query("TRUNCATE TABLE rets_openhouse_huiting")) {
    exit("❌ TRUNCATE failed: ({$conn->errno}) {$conn->error}\n");
}
echo "🗑️  Table truncated.\n";

/* Get token */
$tok = $conn->query("
    SELECT access_token, expires_at
      FROM token_store_huiting
     WHERE token_type='trestle'
  ORDER BY expires_at DESC LIMIT 1
")->fetch_assoc();
if (!$tok) exit("❌ No token found.\n");
$access_token = $tok['access_token'];
echo "✅ Got token.\n";

/* Helper: split ISO8601 datetime into date and time */
function split_iso_datetime($s) {
    if (!$s) return [ '', '' ];
    if (strpos($s, 'T') !== false) {
        try {
            $dt = new DateTime($s);
            return [ $dt->format('Y-m-d'), $dt->format('H:i:s') ];
        } catch (Exception $e) {
            $parts = explode('T', $s, 2);
            $date  = $parts[0] ?? '';
            $time  = isset($parts[1]) ? substr($parts[1], 0, 8) : '';
            return [ $date, $time ];
        }
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $s)) {
        return [ '', $s ];
    }
    return [ '', '' ];
}

/* Prepared statement with 9 placeholders and 9 bound variables */
$sql = "
INSERT INTO rets_openhouse_huiting (
  L_ListingID,
  L_DisplayId,
  OpenHouseDate,
  OH_StartTime,
  OH_EndTime,
  OH_StartDate,
  OH_EndDate,
  OpenHouseStatus,
  all_data,
  updated_date
) VALUES (
  ?, ?,                           
  NULLIF(?, ''),                  
  NULLIF(?, ''),                  
  NULLIF(?, ''),                  
  NULLIF(?, ''),                  
  NULLIF(?, ''),                  
  ?, ?,                           
  NOW()
)
ON DUPLICATE KEY UPDATE
  OH_StartTime    = VALUES(OH_StartTime),
  OH_EndTime      = VALUES(OH_EndTime),
  OH_StartDate    = VALUES(OH_StartDate),
  OH_EndDate      = VALUES(OH_EndDate),
  OpenHouseStatus = VALUES(OpenHouseStatus),
  all_data        = VALUES(all_data),
  updated_date    = NOW()
";
$up = $conn->prepare($sql);
if (!$up) exit("❌ PREPARE failed: ({$conn->errno}) {$conn->error}\n");

$up->bind_param(
    'sssssssss',
    $L_ListingID,     
    $L_DisplayId,     
    $OpenHouseDate,   
    $OH_StartTime,    
    $OH_EndTime,      
    $OH_StartDate,    
    $OH_EndDate,      
    $OpenHouseStatus, 
    $all_data         
);

/* API request for today and future active open houses */
$base  = "https://api-trestle.corelogic.com/trestle/odata/OpenHouse";
$today = date('Y-m-d');
$url   = $base .
        "?%24filter=OpenHouseStatus%20eq%20'Active'%20and%20OpenHouseDate%20ge%20{$today}" .
        "&%24top=200&%24count=true";

$totalFetched = 0;
$okInserted   = 0;
$failCount    = 0;
$skipCount    = 0;

while ($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$access_token}"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30
    ]);
    $json = curl_exec($ch);
    if ($json === false) exit("❌ cURL: " . curl_error($ch) . "\n");
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) exit("❌ API HTTP {$code}\n{$json}\n");

    $resp = json_decode($json);
    $rows = $resp->value ?? [];
    $totalFetched += count($rows);
    echo "📦 Fetched " . count($rows) . " rows (acc={$totalFetched})\n";

    foreach ($rows as $row) {
        $L_ListingID = (string)($row->ListingKey ?? '');
        $L_DisplayId = (string)($row->ListingId  ?? '');

        $OpenHouseDate = (string)($row->OpenHouseDate ?? '');
        $startRaw      = (string)($row->OpenHouseStartTime ?? $row->OpenHouseStartDateTime ?? '');
        $endRaw        = (string)($row->OpenHouseEndTime   ?? $row->OpenHouseEndDateTime   ?? '');

        list($dateFromStart, $OH_StartTime) = split_iso_datetime($startRaw);
        list($dateFromEnd,   $OH_EndTime)   = split_iso_datetime($endRaw);

        if ($OpenHouseDate === '' && $dateFromStart !== '') {
            $OpenHouseDate = $dateFromStart;
        }

        $OH_StartDate = $OpenHouseDate;
        $OH_EndDate   = $OpenHouseDate;

        $OpenHouseStatus = 'Active';
        $all_data        = json_encode($row, JSON_UNESCAPED_UNICODE);

        if ($L_ListingID === '' || $OpenHouseDate === '') {
            $skipCount++;
            continue;
        }

        if (!$up->execute()) {
            $failCount++;
            echo "❌ Insert failed ({$up->errno}): {$up->error} ".
                 "[ListingKey={$L_ListingID} Date={$OpenHouseDate} StartRaw={$startRaw}]\n";
            continue;
        }
        $okInserted++;
    }

    $url = $resp->{'@odata.nextLink'} ?? '';
}

list($finalCnt) = $conn->query("SELECT COUNT(*) FROM rets_openhouse_huiting")->fetch_row();
echo "📊 Summary: fetched={$totalFetched}, ok={$okInserted}, fail={$failCount}, skipped={$skipCount}\n";
echo "📊 Rows in table now: {$finalCnt}\n";
echo "===== OpenHouse ACTIVE SYNC END   " . date('Y-m-d H:i:s') . " =====\n";
