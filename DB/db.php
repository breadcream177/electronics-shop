<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "electronics_shop";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("DB 연결 실패: " . mysqli_connect_error());
}
?>