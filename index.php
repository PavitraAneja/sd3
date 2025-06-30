<?php
include('includes/db_local.php'); // Use local database settings
include('includes/functions.php');
include('includes/pagination.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$filter_sql = build_filter_query($_GET);

$count_sql = "SELECT COUNT(*) as total FROM rets_property $filter_sql";
$count_result = $conn->query($count_sql);
$total = $count_result ? $count_result->fetch_assoc()['total'] : 0;

$sql = "SELECT L_ListingID, L_Address, L_City, L_State, L_Zip, L_SystemPrice, L_Keyword2, LM_Dec_3, LM_Int2_3, L_Photos, L_Remarks, LMD_MP_Latitude as Latitude, LMD_MP_Longitude as Longitude, 
        LA1_UserFirstName, LA1_UserLastName, LO1_OrganizationName, L_Status, created_at
        FROM rets_property $filter_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$listings = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $photos = [];
        if (!empty($row['L_Photos'])) {
            $photos = json_decode($row['L_Photos'], true);
            if (!is_array($photos)) $photos = [];
        }
        $firstPhoto = $photos[0] ?? 'https://via.placeholder.com/300x200?text=No+Photo';
        
        $listings[] = [
            'id' => $row['L_ListingID'] ?? '',
            'address' => ($row['L_Address'] ?? '') . ', ' . ($row['L_City'] ?? '') . ', ' . ($row['L_State'] ?? '') . ' ' . ($row['L_Zip'] ?? ''),
            'price' => isset($row['L_SystemPrice']) ? number_format($row['L_SystemPrice']) : 'N/A',
            'beds' => $row['L_Keyword2'] ?? '-',
            'baths' => $row['LM_Dec_3'] ?? '-',
            'sqft' => $row['LM_Int2_3'] ?? '-',
            'photo' => $firstPhoto,
            'lat' => $row['Latitude'] ?? null,
            'lng' => $row['Longitude'] ?? null,
            'remarks' => $row['L_Remarks'] ?? '',
            'agent_first' => $row['LA1_UserFirstName'] ?? '',
            'agent_last' => $row['LA1_UserLastName'] ?? '',
            'office' => $row['LO1_OrganizationName'] ?? '',
            'status' => $row['L_Status'] ?? '',
            'created_at' => $row['created_at'] ?? ''
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Property Search - SD3</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        .header-actions {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .header-actions a {
            margin-right: 15px;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .header-actions a:hover {
            background: #0056b3;
        }
        .stats {
            background: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .card .agent-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <h1>Property Listings</h1>
    
    <div class="header-actions">
        <a href="index.php">🔄 View Properties</a>
        <a href="openhouse.php">🏠 View Open Houses</a>
    </div>

    <div class="stats">
        <strong>Total Properties:</strong> <?php echo number_format($total); ?> | 
        <strong>Showing:</strong> <?php echo count($listings); ?> properties
        <?php if (!empty($listings)): ?>
        | <strong>Latest Update:</strong> <?php echo date('M j, Y g:i A', strtotime($listings[0]['created_at'])); ?>
        <?php endif; ?>
    </div>

    <form method="GET" class="filters">
        <input type="number" name="min" placeholder="Min Price" value="<?php echo $_GET['min'] ?? ''; ?>">
        <input type="number" name="max" placeholder="Max Price" value="<?php echo $_GET['max'] ?? ''; ?>">
        <input type="number" name="beds" placeholder="Min Beds" value="<?php echo $_GET['beds'] ?? ''; ?>">
        <input type="number" name="baths" placeholder="Min Baths" value="<?php echo $_GET['baths'] ?? ''; ?>">
        <input type="text" name="city" placeholder="City" value="<?php echo $_GET['city'] ?? ''; ?>">
        <button type="submit">Search</button>
    </form>

    <div class="grid">
        <?php if (empty($listings)): ?>
            <div style="text-align: center; padding: 40px; color: #666; grid-column: 1 / -1;">
                <h3>No properties found</h3>
                <p>Try running the sync script to import properties from the Trestle API.</p>
                <a href="sync_properties.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Sync Properties</a>
            </div>
        <?php else: ?>
            <?php foreach($listings as $home): ?>
            <div class="card">
                <a href="property.php?id=<?php echo htmlspecialchars($home['id']); ?>">
                    <img src="<?php echo htmlspecialchars($home['photo']); ?>" alt="Home Photo">
                    <div class="info">
                        <h2>$<?php echo $home['price']; ?></h2>
                        <p><?php echo "{$home['beds']} bd • {$home['baths']} ba • {$home['sqft']} sqft"; ?></p>
                        <p><?php echo $home['address']; ?></p>
                        
                        <?php if (!empty($home['agent_first']) || !empty($home['agent_last'])): ?>
                        <div class="agent-info">
                            <strong>Agent:</strong> <?php echo htmlspecialchars($home['agent_first'] . ' ' . $home['agent_last']); ?>
                            <?php if (!empty($home['office'])): ?>
                            <br><strong>Office:</strong> <?php echo htmlspecialchars($home['office']); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($home['status'])): ?>
                        <span class="status-badge status-<?php echo strtolower($home['status']); ?>">
                            <?php echo htmlspecialchars($home['status']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($total > $limit): ?>
        <?php echo paginate($total, $limit, $page, '?'.http_build_query(array_merge($_GET, ['page' => null])) . '&'); ?>
    <?php endif; ?>

    <?php if (!empty($listings)): ?>
    <div id="map"></div>
    <?php endif; ?>

    <script>
    const listings = <?php echo json_encode($listings); ?>;
    </script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
