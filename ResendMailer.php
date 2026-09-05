<?php

function sendOtpWithResend(string $recipient, string $otpCode): void
{
    global $resendApiKey, $resendFromEmail;

    $apiKey = $resendApiKey ?: getenv('RESEND_API_KEY');
    $fromEmail = $resendFromEmail ?: (getenv('RESEND_FROM_EMAIL') ?: 'Latpost <noreply@latpost.com>');

    if (!$apiKey || !$fromEmail) {
        throw new RuntimeException('Resend mail ayarlari eksik.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL eklentisi gerekli.');
    }

    $payload = json_encode([
        'from' => $fromEmail,
        'to' => [$recipient],
        'subject' => 'Latpost hesap aktivasyon kodunuz',
        'text' => "Latpost hesap aktivasyon kodunuz: {$otpCode}\n\nBu kod 10 dakika gecerlidir.",
        'html' => '<p>Latpost hesap aktivasyon kodunuz:</p>'
            . '<p style="font-size:24px;font-weight:bold;letter-spacing:4px">'
            . htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8')
            . '</p><p>Bu kod 10 dakika gecerlidir.</p>',
    ], JSON_UNESCAPED_SLASHES);

    if ($payload === false) {
        throw new RuntimeException('Mail verisi olusturulamadi.');
    }

    $curl = curl_init('https://api.resend.com/emails');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($response === false || $curlError !== '' || $httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException('OTP maili gonderilemedi.');
    }
}