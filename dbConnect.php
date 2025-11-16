<?php
$servername = "localhost";
$username = "root";
$password = "test1234";  // MySQL root 비밀번호
$dbname = "blankGame";

// DB 연결
$conn = new mysqli($servername, $username, $password, $dbname);

// 연결 확인
if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

// 한글 깨짐 방지
$conn->set_charset("utf8mb4");
?>

