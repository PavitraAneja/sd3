<?php
include('includes/db.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = $_GET['id'] ?? '';
if (!$id) {
    echo "No listing ID provided.";
    exit;
}

$sql = "SELECT L_ListingID, L_Address, L_City, L_State, L_Zip, L_SystemPrice, L_Keyword2, LM_Dec_3, LM_Int2_3, L_Photos, L_Remarks, LMD_MP_Latitude as Latitude, LMD_MP_Longitude as Longitude 
        FROM rets_property WHERE L_ListingID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "Listing not found.";
    exit;
}

$photos = [];
if (!empty($row['L_Photos'])) {
    $photos = json_decode($row['L_Photos'], true);
    if (!is_array($photos)) $photos = [];
}
$firstPhoto = $photos[0] ?? 'https://via.placeholder.com/300x200?text=No+Photo';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($row['L_Address']); ?> - Calisearch</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1><?php echo htmlspecialchars($row['L_Address']); ?></h1>
    <p><?php echo htmlspecialchars($row['L_City'] . ', ' . $row['L_State'] . ' ' . $row['L_Zip']); ?></p>
    <p><strong>Price:</strong> $<?php echo number_format($row['L_SystemPrice']); ?></p>
    <p><strong>Beds:</strong> <?php echo $row['L_Keyword2']; ?> |
       <strong>Baths:</strong> <?php echo $row['LM_Dec_3']; ?> |
       <strong>SqFt:</strong> <?php echo $row['LM_Int2_3']; ?></p>

    <div class="gallery">
        <?php foreach($photos as $img): ?>
            <img src="<?php echo htmlspecialchars($img); ?>" alt="Property Photo" style="max-width:300px; margin:10px;">
        <?php endforeach; ?>
    </div>

    <h2>About this home</h2>
    <p><?php echo nl2br(htmlspecialchars($row['L_Remarks'])); ?></p>

    <p><a href="index.php">← Back to Listings</a></p>
</body>
</html>
