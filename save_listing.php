<?php
session_start();
include('api/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$listing_id = $_POST['listing_id'];

$stmt = $conn->prepare("INSERT IGNORE INTO saved_listings (user_id, listing_id) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $listing_id);
$stmt->execute();
$stmt->close();

$redirect_url = "property.php?id=" . urlencode($listing_id);
header("Location: $redirect_url");
exit();
?>