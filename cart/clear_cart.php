<?php
session_start();
require_once __DIR__ . "/../DB/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$IMG_BASE = "../images/";
$user_id = (int)$_SESSION['user_id'];

$sql = "DELETE FROM cart WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

header("Location: cart.php");
exit;