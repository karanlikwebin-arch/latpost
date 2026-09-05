<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';
require_once 'Auth.php';
$userId = requireAuthenticatedUser($db);
$action = array_key_exists('Action', $_POST) ? postString('Action', 20) : 'update';

if ($action === null) {
    echo json_encode(["status" => "error", "message" => "Gecersiz islem."]);
    exit;
}

// 1. PROFİL GÜNCELLEME
if ($action === 'update') {
    $nameSurname = postString('NameSurname', 100);
    $mail = postString('Mail', 150);
    $avatarProvided = array_key_exists('UserAvatar', $_POST);
    $avatar = $avatarProvided ? postString('UserAvatar', 255) : null;
    $passwordProvided = array_key_exists('Password', $_POST);
    $password = $passwordProvided ? postString('Password', 4096) : null;

    if ($nameSurname === null || $mail === null || ($avatarProvided && $avatar === null) || ($passwordProvided && $password === null) || $nameSurname === '' || $mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Ad soyad ve mail alanlari bos birakilamaz."]);
        exit;
    }

    // Mail benzersizlik kontrolü (başka kullanıcıda var mı?)
    $stmtMail = $db->prepare("SELECT id FROM User WHERE Mail = ? AND id != ? LIMIT 1");
    $stmtMail->execute([$mail, $userId]);
    if ($stmtMail->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["status" => "error", "message" => "Bu mail adresi baska bir kullaniciya ait."]);
        exit;
    }

    try {
        if ($password !== null && $password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($avatar === null) {
            $stmt = $db->prepare("UPDATE User SET NameSurname = ?, Mail = ?, Password = ? WHERE id = ? AND UserDeleted = 0");
            $stmt->execute([$nameSurname, $mail, $hashedPassword, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE User SET NameSurname = ?, Mail = ?, UserAvatar = ?, Password = ? WHERE id = ? AND UserDeleted = 0");
            $stmt->execute([$nameSurname, $mail, $avatar, $hashedPassword, $userId]);
        }
    } else {
        if ($avatar === null) {
            $stmt = $db->prepare("UPDATE User SET NameSurname = ?, Mail = ? WHERE id = ? AND UserDeleted = 0");
            $stmt->execute([$nameSurname, $mail, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE User SET NameSurname = ?, Mail = ?, UserAvatar = ? WHERE id = ? AND UserDeleted = 0");
            $stmt->execute([$nameSurname, $mail, $avatar, $userId]);
        }
        }
    } catch (PDOException $exception) {
        echo json_encode(["status" => "error", "message" => "Profil guncellenemedi."]);
        exit;
    }

    echo json_encode(["status" => "success", "message" => "Profil basariyla guncellendi."]);
}

// 2. TEKİL OTURUMU SONLANDIRMA (Çıkış Yap)
else if ($action === 'logout') {
    $stmt = $db->prepare("DELETE FROM UserToken WHERE UserId = ?");
    $stmt->execute([$userId]);
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
    try {
        $db->beginTransaction();
        $stmtUser = $db->prepare("UPDATE User SET UserDeleted = 1 WHERE id = ? AND UserDeleted = 0");
        $stmtUser->execute([$userId]);
        $stmtToken = $db->prepare("DELETE FROM UserToken WHERE UserId = ?");
        $stmtToken->execute([$userId]);
        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(["status" => "error", "message" => "Hesap silinemedi."]);
        exit;
    }

    echo json_encode(["status" => "success", "message" => "Hesabiniz basariyla silindi."]);
} else {
    echo json_encode(["status" => "error", "message" => "Gecersiz islem."]);
}
