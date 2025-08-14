<?php
/********************************************************************
 * sync_activelisting_huiting.php – fast incremental (no full rebuild)
 ********************************************************************/
date_default_timezone_set('America/Chicago');
echo "===== ActiveListing FAST SYNC " . date('Y-m-d H:i:s') . " =====\n";

require_once __DIR__.'/db.php';
if ($conn->connect_errno) exit("DB error: {$conn->connect_error}\n");
echo "✅ DB connected.\n";

/* 1. Ensure target table & index */
$conn->query("CREATE TABLE IF NOT EXISTS activelistings LIKE rets_property_huiting");
$conn->query("ALTER TABLE activelistings ADD UNIQUE KEY uk_listing (L_ListingID)");
echo "✅ Table/index OK.\n";

/* 2. Insert new active listings from last 15 min */
$sqlInsert = "
INSERT IGNORE INTO activelistings
SELECT *
  FROM rets_property_huiting
 WHERE L_Status = 'Active'
   AND updated_date >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
";
$conn->query($sqlInsert);
echo "➕ Inserted " . $conn->affected_rows . " new rows.\n";

/* 3. Delete/mark rows no longer Active */
$sqlDelete = "
DELETE a
  FROM activelistings a
  LEFT JOIN rets_property_huiting p
    ON a.L_ListingID = p.L_ListingID
   AND p.L_Status = 'Active'
 WHERE p.L_ListingID IS NULL
   AND a.updated_date < DATE_SUB(NOW(), INTERVAL 20 MINUTE)   -- grace window
";
$conn->query($sqlDelete);
echo "🗑️  Removed " . $conn->affected_rows . " outdated rows.\n";

echo "===== FAST SYNC END " . date('Y-m-d H:i:s') . " =====\n";
