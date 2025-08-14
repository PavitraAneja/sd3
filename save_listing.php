<?php
session_start();
include('api/db.php');


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$listing_id = $_POST['listing_id'];

// $result = mysqli_query($conn, "SELECT first_name, email FROM users WHERE id = $userId");
// $user = mysqli_fetch_assoc($result);
// $propertyId = $_POST['listing_id'];
// $pResult = mysqli_query($conn, "SELECT title FROM properties WHERE id = $propertyId");
// $property = mysqli_fetch_assoc($pResult);
// $propertyTitle = $property['title'];
// $propertyLink = "https://yourdomain.org/property.php?id=$propertyId";

// sendSavedListingAlert($user['email'], $user['first_name'], $propertyTitle, $propertyLink);

$stmt = $conn->prepare("INSERT IGNORE INTO saved_listings (user_id, listing_id) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $listing_id);
$stmt->execute();
$stmt->close();

$redirect_url = "property.php?id=" . urlencode($listing_id);
header("Location: $redirect_url");
exit();
?>