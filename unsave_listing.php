<?php
session_start();
include('api/db.php');

if (!isset($_SESSION['user_id']) || !isset($_POST['listing_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$listing_id = $_POST['listing_id'];

$stmt = $conn->prepare("DELETE FROM saved_listings WHERE user_id = ? AND listing_id = ?");
$stmt->bind_param("is", $user_id, $listing_id);
$stmt->execute();
$stmt->close();

header("Location: property.php?id=" . urlencode($listing_id));
exit();