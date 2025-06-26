<?php
include('includes/db.php');
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

$sql = "SELECT L_ListingID, L_Address, L_City, L_State, L_Zip, L_SystemPrice, L_Keyword2, LM_Dec_3, LM_Int2_3, L_Photos, L_Remarks, LMD_MP_Latitude as Latitude, LMD_MP_Longitude as Longitude 
        FROM rets_property $filter_sql LIMIT $limit OFFSET $offset";
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
            'remarks' => $row['L_Remarks'] ?? ''
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Property Search - Calisearch</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body>
    <h1>Property Listings</h1>
    <h2>TEST FOR LOCAL DEVELOPMENT</h2>

    <form method="GET" class="filters">
        <input type="number" name="min" placeholder="Min Price" value="<?php echo $_GET['min'] ?? ''; ?>">
        <input type="number" name="max" placeholder="Max Price" value="<?php echo $_GET['max'] ?? ''; ?>">
        <input type="number" name="beds" placeholder="Min Beds" value="<?php echo $_GET['beds'] ?? ''; ?>">
        <input type="number" name="baths" placeholder="Min Baths" value="<?php echo $_GET['baths'] ?? ''; ?>">
        <input type="text" name="city" placeholder="City" value="<?php echo $_GET['city'] ?? ''; ?>">
        <button type="submit">Search</button>
    </form>

    <div class="grid">
        <?php foreach($listings as $home): ?>
        <div class="card">
            <a href="property.php?id=<?php echo htmlspecialchars($home['id']); ?>">
                <img src="<?php echo htmlspecialchars($home['photo']); ?>" alt="Home Photo">
                <div class="info">
                    <h2>$<?php echo $home['price']; ?></h2>
                    <p><?php echo "{$home['beds']} bd • {$home['baths']} ba • {$home['sqft']} sqft"; ?></p>
                    <p><?php echo $home['address']; ?></p>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <?php echo paginate($total, $limit, $page, '?'.http_build_query(array_merge($_GET, ['page' => null])) . '&'); ?>

    <div id="map"></div>

    <script>
    const listings = <?php echo json_encode($listings); ?>;
    </script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
