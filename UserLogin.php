<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';
require_once 'Auth.php';

$mail = postString('Mail', 150);
$password = postString('Password', 4096);

if ($mail === null || $password === null || $mail === '' || $password === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Mail ve sifre alanlari zorunludur."]);
    exit;
}

$stmt = $db->prepare("SELECT id, Password, UserActive FROM User WHERE Mail = ? AND UserDeleted = 0");
$stmt->execute([$mail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['Password'])) {
    echo json_encode(["status" => "error", "message" => "Mail veya sifre hatali."]);
    exit;
}

// Token oluşturma
$userId = $user['id'];
$userToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $userToken);

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id FROM User WHERE id = ? AND UserDeleted = 0 FOR UPDATE");
    $stmt->execute([$userId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $db->rollBack();
        echo json_encode(["status" => "error", "message" => "Kullanici bulunamadi."]);
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM UserToken WHERE UserId = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$userId]);
    $existingToken = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingToken) {
        $stmt = $db->prepare("UPDATE UserToken SET UserToken = ? WHERE id = ?");
        $stmt->execute([$tokenHash, $existingToken['id']]);

        $stmt = $db->prepare("DELETE FROM UserToken WHERE UserId = ? AND id != ?");
        $stmt->execute([$userId, $existingToken['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO UserToken (UserId, UserToken) VALUES (?, ?)");
        $stmt->execute([$userId, $tokenHash]);
    }

    $db->commit();
    $tokenResult = true;
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $tokenResult = false;
}

if ($tokenResult) {
    if ((int) $user['UserActive'] !== 1) {
        echo json_encode([
            "status" => "activation_required",
            "message" => "Hesabinizi kullanmak icin once OTP kodu ile aktif etmelisiniz.",
            "token" => $userToken
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Giris basarili.",
        "token" => $userToken
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Oturum olusturulamadi."]);
}
