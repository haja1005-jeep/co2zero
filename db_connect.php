<?php
// db_connect.php
$host = 'localhost';
$user = 'im4u798';      // 사용자 ID
$pass = 'dbi73043365k!!';  // 사용자 비번
$db   = 'im4u798';      // DB명

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["error" => "DB Connection Failed: " . $conn->connect_error]));
}

// 한글 깨짐 방지
$conn->set_charset("utf8");
?>