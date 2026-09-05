<?php
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'veritabani_adi';
$username = getenv('DB_USER') ?: 'veritabani_kullanici';
$password = getenv('DB_PASSWORD') ?: 'veritabani_sifre';
$port = getenv('DB_PORT') ?: '3306';

// Resend ayarlari. API anahtarini kaynak koda yazmayin.
$resendApiKey = '';
$resendFromEmail = 'Latpost <noreply@latpost.com>';

try {
    $db = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Veritabani baglanti hatasi"]);
    exit;
}
