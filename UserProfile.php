<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';
require_once 'Auth.php';
$userId = requireAuthenticatedUser($db);

// Kullanici bilgilerini cekme (Sifre hariç)
$stmt = $db->prepare("SELECT id, UserAvatar, NameSurname, Mail, UserCreated FROM User WHERE id = ? AND UserDeleted = 0 AND UserActive = 1");
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
