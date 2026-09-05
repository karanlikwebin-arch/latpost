<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';

// Token doğrulama
$headers = apache_request_headers();
$token = $headers['Authorization'] ?? $_POST['Token'] ?? '';

if (empty($token)) {
    echo json_encode(["status" => "error", "message" => "Oturum token'i gerekli."]);
    exit;
}

$stmt = $db->prepare("SELECT UserId FROM UserToken WHERE UserToken = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo json_encode(["status" => "error", "message" => "Gecersiz veya suresi dolmus oturum."]);
    exit;
}

$userId = $tokenData['UserId'];
$action = $_POST['Action'] ?? 'update'; // update, logout, terminate_all, delete

// 1. PROFİL GÜNCELLEME
if ($action === 'update') {
    $nameSurname = $_POST['NameSurname'] ?? '';
    $mail = $_POST['Mail'] ?? '';
    $avatar = $_POST['UserAvatar'] ?? '';
    $password = $_POST['Password'] ?? '';

    if (empty($nameSurname) || empty($mail)) {
        echo json_encode(["status" => "error", "message" => "Ad soyad ve mail alanlari bos birakilamaz."]);
        exit;
    }

    // Mail benzersizlik kontrolü (başka kullanıcıda var mı?)
    $stmtMail = $db->prepare("SELECT id FROM User WHERE Mail = ? AND id != ?");
    $stmtMail->execute([$mail, $userId]);
    if ($stmtMail->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Bu mail adresi baska bir kullaniciya ait."]);
        exit;
    }

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE User SET NameSurname = ?, Mail = ?, UserAvatar = ?, Password = ? WHERE id = ?");
        $stmt->execute([$nameSurname, $mail, $avatar, $hashedPassword, $userId]);
    } else {
        $stmt = $db->prepare("UPDATE User SET NameSurname = ?, Mail = ?, UserAvatar = ? WHERE id = ?");
        $stmt->execute([$nameSurname, $mail, $avatar, $userId]);
    }

    echo json_encode(["status" => "success", "message" => "Profil basariyla guncellendi."]);
}

// 2. TEKİL OTURUMU SONLANDIRMA (Çıkış Yap)
else if ($action === 'logout') {
    $stmt = $db->prepare("DELETE FROM UserToken WHERE UserToken = ?");
    $stmt->execute([$token]);
    echo json_encode(["status" => "success", "message" => "Oturum sonlandirildi."]);
}

// 3. TÜM OTURUMLARI SONLANDIRMA (Diğer cihazlardan çıkış yap)
else if ($action === 'terminate_all') {
    $stmt = $db->prepare("DELETE FROM UserToken WHERE UserId = ?");
    $stmt->execute([$userId]);
    echo json_encode(["status" => "success", "message" => "Tum aktif oturumlar kapatildi."]);
}

// 4. HESAP SİLME (Soft Delete)
else if ($action === 'delete') {
    // Kullanıcıyı pasife çek
    $stmtUser = $db->prepare("UPDATE User SET UserDeleted = 1 WHERE id = ?");
    $stmtUser->execute([$userId]);

    // Aktif token'larını temizle
    $stmtToken = $db->prepare("DELETE FROM UserToken WHERE UserId = ?");
    $stmtToken->execute([$userId]);

    echo json_encode(["status" => "success", "message" => "Hesabiniz basariyla silindi."]);
} else {
    echo json_encode(["status" => "error", "message" => "Gecersiz islem."]);
}
