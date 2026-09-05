<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';
require_once 'Auth.php';
require_once 'ResendMailer.php';

$nameSurname = postString('NameSurname', 100);
$mail = postString('Mail', 150);
$password = postString('Password', 4096);

if ($nameSurname === null || $mail === null || $password === null || $nameSurname === '' || $mail === '' || $password === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Tum alanlari doldurun."]);
    exit;
}

// Mail kontrolü
$stmt = $db->prepare("SELECT id FROM User WHERE Mail = ? LIMIT 1");
$stmt->execute([$mail]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(["status" => "error", "message" => "Bu mail adresi zaten kayitli."]);
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$userToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $userToken);

try {
    $db->beginTransaction();

    $stmt = $db->prepare("INSERT INTO User (NameSurname, Mail, Password) VALUES (?, ?, ?)");
    $stmt->execute([$nameSurname, $mail, $hashedPassword]);
    $userId = (int) $db->lastInsertId();

    do {
        $otpCode = (string) random_int(100000, 999999);
        $stmt = $db->prepare("SELECT id FROM UserOtp WHERE OtpCode = ? LIMIT 1");
        $stmt->execute([$otpCode]);
    } while ($stmt->fetch(PDO::FETCH_ASSOC));

    $stmt = $db->prepare("INSERT INTO UserOtp (UserId, OtpCode) VALUES (?, ?)");
    $stmt->execute([$userId, $otpCode]);

    $stmt = $db->prepare("INSERT INTO UserToken (UserId, UserToken) VALUES (?, ?)");
    $stmt->execute([$userId, $tokenHash]);

    sendOtpWithResend($mail, $otpCode);
    $db->commit();
    $result = true;
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $result = false;
}

if ($result) {
    echo json_encode([
        "status" => "activation_required",
        "message" => "Kayit basarili. OTP kodu ile hesabinizi aktif edin.",
        "token" => $userToken
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Kayit olusturulamadi."]);
}
