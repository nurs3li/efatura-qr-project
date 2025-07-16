<?php

function convertPdfToPng($pdfPath, $outputBasePath) {
    $pdftoppm = escapeshellcmd(__DIR__ . '/../tools/pdftoppm.exe');
   $cmd = "$pdftoppm -r 300 -png " . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputBasePath);

    shell_exec($cmd);
    return $outputBasePath . '-1.png'; // sadece ilk sayfa
}

function readQrFromImage($imagePath) {
    $zbarPath = escapeshellcmd(__DIR__ . '/../tools/zbarimg.exe');
    $cmd = "$zbarPath -q " . escapeshellarg($imagePath);
    $output = shell_exec($cmd);
    preg_match('/QR-Code:(.*)/', $output, $matches);
    return isset($matches[1]) ? trim($matches[1]) : null;
}

function parseQrData($qrData) {
    $parsed = [];
    preg_match_all('/(\w+)=["]?([^"\s]+)["]?/', $qrData, $fields);
    if (!empty($fields[1]) && !empty($fields[2])) {
        foreach ($fields[1] as $i => $key) {
            $parsed[strtolower($key)] = $fields[2][$i];
        }
    }
    return $parsed;
}

function getQrDataFromJson($jsonFile) {
    if (!file_exists($jsonFile)) return null;
    $data = json_decode(file_get_contents($jsonFile), true);
    return isset($data['qr_raw']) ? $data['qr_raw'] : null;
}
function triggerWebhooks($data)
{
    global $pdo;

    // 1️⃣ Webhook adresi
    $webhookUrl = "https://webhook.site/00d1315c-9fe5-4f74-ad84-5fb1abdc6417";

    // 2️⃣ Kullanıcı bilgilerini POST veya SESSION üzerinden al
    $kullaniciAdi = $_POST['username'] ?? ($_SESSION['username'] ?? 'anonim');
    $token = $_POST['token'] ?? ($_SESSION['token'] ?? 'no-token');
    $firmaKodu = $_POST['firma_kodu'] ?? ($_SESSION['firma_kodu'] ?? 'bilinmiyor');

    // 3️⃣ JSON içine kullanıcı bilgilerini ekle
    $data['auth'] = [
        'token' => $token,
        'user' => $kullaniciAdi,
        'firma_kodu' => $firmaKodu
    ];

    // 4️⃣ Aynı URL-token varsa tekrar ekleme
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM webhooklar WHERE url = :url AND token = :token AND kullanici_adi = :kullanici_adi");
        $stmt->execute([
            ':url' => $webhookUrl,
            ':token' => $token,
            ':kullanici_adi' => $kullaniciAdi
        ]);
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            $stmt = $pdo->prepare("
                INSERT INTO webhooklar (url, aktif, eklenme_tarihi, token, kullanici_adi)
                VALUES (:url, 1, NOW(), :token, :kullanici_adi)
            ");
            $stmt->execute([
                ':url' => $webhookUrl,
                ':token' => $token,
                ':kullanici_adi' => $kullaniciAdi
            ]);
        }
    } catch (PDOException $e) {
        file_put_contents(__DIR__ . '/webhook_hatalari.log', date("Y-m-d H:i:s") . " | DB INSERT HATA | " . $e->getMessage() . "\n", FILE_APPEND);
        return;
    }

    // 5️⃣ Webhook gönder
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 6️⃣ Gönderim logu
    try {
        $log = $pdo->prepare("
            INSERT INTO webhook_log (url, payload, response_code, created_at)
            VALUES (:url, :payload, :response_code, NOW())
        ");
        $log->execute([
            ':url' => $webhookUrl,
            ':payload' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ':response_code' => $httpCode
        ]);
    } catch (PDOException $e) {
        file_put_contents(__DIR__ . '/webhook_hatalari.log', date("Y-m-d H:i:s") . " | LOG INSERT HATA | " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // 7️⃣ CURL hatası varsa logla
    if ($curlError) {
        file_put_contents(__DIR__ . '/debug_trigger.log', date("Y-m-d H:i:s") . " | CURL ERROR | $curlError\n", FILE_APPEND);
    }
}

