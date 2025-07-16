<?php
require_once 'qrfatura/config/db.php';

function generateSecureToken() {
    return hash('sha256', random_bytes(32));
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'] ?? '';
    $kullaniciAdi = $_POST['kullanici_adi'] ?? 'anonim';
    $token = $_POST['token'] ?? '';

    if (empty($token)) {
        $token = generateToken(); // rastgele üret
    }

    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO webhooklar (url, token, kullanici_adi, aktif, eklenme_tarihi)
            VALUES (:url, :token, :kullanici_adi, 1, NOW())
        ");
        $stmt->execute([
            ':url' => $url,
            ':token' => $token,
            ':kullanici_adi' => $kullaniciAdi
        ]);

        echo "✅ Webhook başarıyla kaydedildi.<br>";
        echo "<strong>Token:</strong> $token";
    } catch (PDOException $e) {
        echo "❌ DB Hatası: " . $e->getMessage();
    }
}
