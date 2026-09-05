<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';

$headers = apache_request_headers();
$token = $headers['Authorization'] ?? $_POST['Token'] ?? $_GET['Token'] ?? '';

if (empty($token)) {
    echo json_encode(["status" => "error", "message" => "Token bulunamadi."]);
    exit;
}

// Token ile UserId bulma
$stmt = $db->prepare("SELECT UserId FROM UserToken WHERE UserToken = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo json_encode(["status" => "error", "message" => "Gecersiz token."]);
    exit;
}

$userId = $tokenData['UserId'];

// Kullanici bilgilerini cekme (Sifre hariç)
$stmt = $db->prepare("SELECT id, UserAvatar, NameSurname, Mail, UserCreated FROM User WHERE id = ? AND UserDeleted = 0");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Kullanici bulunamadi."]);
    exit;
}

echo json_encode([
    "status" => "success",
    "data" => $user
]);
