<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';

$nameSurname = $_POST['NameSurname'] ?? '';
$mail = $_POST['Mail'] ?? '';
$password = $_POST['Password'] ?? '';

if (empty($nameSurname) || empty($mail) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Tum alanlari doldurun."]);
    exit;
}

// Mail kontrolü
$stmt = $db->prepare("SELECT id FROM User WHERE Mail = ?");
$stmt->execute([$mail]);
if ($stmt->rowCount() > 0) {
    echo json_encode(["status" => "error", "message" => "Bu mail adresi zaten kayitli."]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("INSERT INTO User (NameSurname, Mail, Password) VALUES (?, ?, ?)");
$result = $stmt->execute([$nameSurname, $mail, $hashedPassword]);

if ($result) {
    echo json_encode(["status" => "success", "message" => "Kayit basarili."]);
} else {
    echo json_encode(["status" => "error", "message" => "Kayit olusturulamadi."]);
}
