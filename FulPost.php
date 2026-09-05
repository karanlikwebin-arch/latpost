<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'DbConfig.php';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Aktif postları en yeniden eskiye doğru 10'arlı gruplar halinde çekme
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
    WHERE p.Postdeleted = 0 AND u.UserDeleted = 0
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($posts)) {
    echo json_encode(["status" => "success", "message" => "Daha fazla post bulunamadi.", "data" => []]);
    exit;
}

// Her posta ait görselleri ekleme
foreach ($posts as &$post) {
    $stmtPic = $db->prepare("SELECT PostPicture FROM PostPicture WHERE PostId = ?");
    $stmtPic->execute([$post['PostId']]);
    $pictures = $stmtPic->fetchAll(PDO::FETCH_ASSOC);
    $post['Pictures'] = array_column($pictures, 'PostPicture');
}
unset($post);

echo json_encode([
    "status" => "success",
    "page" => $page,
    "data" => $posts
]);
