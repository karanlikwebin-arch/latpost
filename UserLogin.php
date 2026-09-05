<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';

$mail = $_POST['Mail'] ?? '';
$password = $_POST['Password'] ?? '';

if (empty($mail) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Mail ve sifre alanlari zorunludur."]);
    exit;
}

$stmt = $db->prepare("SELECT id, Password FROM User WHERE Mail = ? AND UserDeleted = 0");
$stmt->execute([$mail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['Password'])) {
    echo json_encode(["status" => "error", "message" => "Mail veya sifre hatali."]);
    exit;
}

// Token oluşturma
$userId = $user['id'];
$userToken = bin2hex(random_bytes(32));

$stmt = $db->prepare("INSERT INTO UserToken (UserId, UserToken) VALUES (?, ?)");
$tokenResult = $stmt->execute([$userId, $userToken]);

if ($tokenResult) {
    echo json_encode([
        "status" => "success",
        "message" => "Giris basarili.",
        "token" => $userToken
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Oturum olusturulamadi."]);
}
