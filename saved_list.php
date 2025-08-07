<?php
session_start();
include('api/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get saved listing IDs
$query = "SELECT listing_id FROM saved_listings WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$saved_ids = [];
while ($row = $result->fetch_assoc()) {
    $saved_ids[] = $row['listing_id'];
}
$stmt->close();

// Fetch saved listing data
$listings = [];
if (count($saved_ids) > 0) {
    $placeholders = implode(',', array_fill(0, count($saved_ids), '?'));
    $types = str_repeat('s', count($saved_ids));
    
    $sql = "SELECT * FROM rets_property_yu WHERE L_ListingID IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$saved_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $photos = !empty($row['L_Photos']) ? json_decode($row['L_Photos'], true) : [];
        $firstPhoto = $photos[0] ?? 'https://via.placeholder.com/300x200?text=No+Photo';
        $listings[] = [
            'id' => $row['L_ListingID'],
            'address' => "{$row['L_Address']}, {$row['L_City']}, {$row['L_State']} {$row['L_Zip']}",
            'price' => number_format($row['L_SystemPrice']),
            'photo' => $firstPhoto
        ];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Saved Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4">My Saved Listings</h2>

    <?php if (empty($listings)): ?>
        <div class="alert alert-info">You haven’t saved any listings yet.</div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($listings as $listing): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="<?php echo htmlspecialchars($listing['photo']); ?>" class="card-img-top" alt="Photo">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($listing['address']); ?></h5>
                            <p class="card-text"><strong>Price:</strong> $<?php echo $listing['price']; ?></p>
                            <a href="property.php?id=<?php echo urlencode($listing['id']); ?>&ref=saved" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>