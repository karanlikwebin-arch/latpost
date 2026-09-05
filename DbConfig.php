<?php
$host = 'localhost';
$dbname = 'veritabani_adi';
$username = 'veritabani_kullanici';
$password = 'veritabani_sifre';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Veritabani baglanti hatasi"]);
    exit;
}
