<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';
require_once 'Auth.php';

$userId = requireAuthenticatedUser($db);

$postId = $_GET['id'] ?? $_POST['id'] ?? '';

if (!is_string($postId) || !ctype_digit($postId) || (int) $postId < 1) {
    echo json_encode(["status" => "error", "message" => "Gecerli bir Post ID belirtilmelidir."]);
    exit;
}

// Post ve Yazara ait bilgileri cekme
$stmt = $db->prepare("
    SELECT 
        p.id AS PostId,
        p.PostCategory,
        p.PostContent,
        p.PostCreated,
        u.id AS UserId,
        u.NameSurname,
        u.UserAvatar
    FROM Post p
    JOIN User u ON p.UserId = u.id
    WHERE p.id = ? AND p.Postdeleted = 0 AND u.UserDeleted = 0
");
$stmt->execute([$postId]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo json_encode(["status" => "error", "message" => "Post bulunamadi veya silinmis."]);
    exit;
}

// Posta ait resimleri cekme
$stmtPic = $db->prepare("SELECT PostPicture FROM PostPicture WHERE PostId = ?");
$stmtPic->execute([$postId]);
$pictures = $stmtPic->fetchAll(PDO::FETCH_ASSOC);

// Resimleri dizi olarak posta ekle
$post['Pictures'] = array_column($pictures, 'PostPicture');

echo json_encode([
    "status" => "success",
    "data" => $post
]);
