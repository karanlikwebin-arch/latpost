<?php

function requestToken(): ?string
{
    $token = $_POST['Token'] ?? null;

    if (!is_string($token)) {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $headers = is_array($headers) ? $headers : [];
        $authorization = null;
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $authorization = $value;
                break;
            }
        }
        $authorization ??= $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        $token = is_string($authorization) ? $authorization : null;
    }

    if ($token === null) {
        return null;
    }

    $token = trim($token);
    $token = preg_replace('/^Bearer\s+/i', '', $token);

    return is_string($token) && preg_match('/^[a-f0-9]{64}$/i', $token) === 1
        ? $token
        : null;
}

function requireAuthenticatedUser(PDO $db, bool $requireActive = true): int
{
    $token = requestToken();

    if ($token === null) {
        echo json_encode(["status" => "error", "message" => "Gecersiz veya eksik token."]);
        exit;
    }

    $tokenHash = hash('sha256', $token);
    $stmt = $db->prepare(
        'SELECT t.id, t.UserId, t.UserToken, u.UserActive
         FROM UserToken t
         INNER JOIN User u ON u.id = t.UserId
         WHERE t.UserToken IN (?, ?) AND u.UserDeleted = 0
         LIMIT 1'
    );
    $stmt->execute([$tokenHash, $token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        echo json_encode(["status" => "error", "message" => "Gecersiz token."]);
        exit;
    }

    if ($requireActive && (int) $tokenData['UserActive'] !== 1) {
        echo json_encode([
            "status" => "activation_required",
            "message" => "Hesabinizi kullanmak icin once OTP kodu ile aktif etmelisiniz."
        ]);
        exit;
    }

    if (hash_equals($token, (string) $tokenData['UserToken'])) {
        $stmt = $db->prepare("UPDATE UserToken SET UserToken = ? WHERE id = ?");
        $stmt->execute([$tokenHash, $tokenData['id']]);
    }

    return (int) $tokenData['UserId'];
}

function postString(string $key, int $maxLength): ?string
{
    if (!array_key_exists($key, $_POST) || !is_string($_POST[$key])) {
        return null;
    }

    $value = trim($_POST[$key]);
    return strlen($value) <= $maxLength ? $value : null;
}