<?php
// DB 연결 설정
$host = '127.0.0.1';
$dbname = 'POLL1';
$username = 'root';
$password = 'YOUR_DB_PASSWORD_HERE'; // 실제 비밀번호를 입력하세요

// 관리자 비밀번호
$admin_password = 'YOUR_ADMIN_PASSWORD_HERE';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
