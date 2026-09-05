<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';

$otpCode = $_GET['otp'] ?? null;

if (!is_string($otpCode) || preg_match('/^\d{6}$/', $otpCode) !== 1) {
    echo json_encode(["status" => "error", "message" => "Gecerli bir OTP kodu girilmelidir."]);
    exit;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare(
        "SELECT o.id, o.UserId
         FROM UserOtp o
         INNER JOIN User u ON u.id = o.UserId
         WHERE o.OtpCode = ?
           AND o.OtpCreated >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
           AND u.UserDeleted = 0
           AND u.UserActive = 0
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->execute([$otpCode]);
    $otp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$otp) {
        $db->rollBack();
        echo json_encode(["status" => "error", "message" => "OTP kodu gecersiz veya suresi dolmus."]);
        exit;
    }

    $stmt = $db->prepare("UPDATE User SET UserActive = 1 WHERE id = ? AND UserDeleted = 0");
    $stmt->execute([$otp['UserId']]);

    $stmt = $db->prepare("DELETE FROM UserOtp WHERE id = ?");
    $stmt->execute([$otp['id']]);

    $db->commit();
    echo json_encode(["status" => "success", "message" => "Hesap basariyla aktif edildi."]);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(["status" => "error", "message" => "Hesap aktif edilemedi."]);
}