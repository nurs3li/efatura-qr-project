<?php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/qr_helpers.php';

define('ROOT_DIR', realpath(__DIR__ . '/../../') . '/');

class qr {

public function uploadDocument()
{
    session_start(); // 🔒 Oturumu başlat
    global $pdo;

    if (!isset($_FILES['qrimage']) || $_FILES['qrimage']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        die("Dosya yüklenemedi.");
    }

    $uploadDir = ROOT_DIR . 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $pdfPath = $uploadDir . 'fatura1.pdf';
    move_uploaded_file($_FILES['qrimage']['tmp_name'], $pdfPath);

    $tempOutputBase = ROOT_DIR . 'images/temp_output';
    $imagePath = $this->convertPdfToPng($pdfPath, $tempOutputBase);
    if (!file_exists($imagePath)) {
        die("PNG oluşturulamadı.");
    }

    $qrData = $this->readQrFromImage($imagePath);
    if (!$qrData) {
        die("QR kodu okunamadı.");
    }

    $qrDecoded = json_decode($qrData, true);
    if (!isset($qrDecoded['ettn'])) {
        die("Geçerli bir ETTN bulunamadı. QR: <pre>$qrData</pre>");
    }

    $ettn = $qrDecoded['ettn'];
    $json = [
        'ettn' => $ettn,
        'qr_raw' => $qrData
    ];

    $finalImage = ROOT_DIR . "images/{$ettn}.png";
    rename($imagePath, $finalImage);

    $jsonDir = ROOT_DIR . 'jsonlar/';
    if (!is_dir($jsonDir)) mkdir($jsonDir, 0777, true);
    file_put_contents("{$jsonDir}/{$ettn}.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    try {
        $stmt = $pdo->prepare("INSERT INTO faturalar (ettn, qr_raw) VALUES (:ettn, :qr_raw)");
        $stmt->execute([':ettn' => $ettn, ':qr_raw' => $qrData]);
        echo "<p>✅ Veritabanına eklendi.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "<p>⚠️ Bu ETTN zaten kayıtlı. Webhook yine gönderilecek.</p>";
        } else {
            die("DB kayıt hatası: " . $e->getMessage());
        }
    }

    // 🔐 Kullanıcı bilgilerini SESSION'dan alıyoruz
    $json['auth'] = [
        'token' => $_SESSION['token'] ?? 'no-token',
        'user' => $_SESSION['username'] ?? 'anonim',
        'firma_kodu' => $_SESSION['firma_kodu'] ?? 'bilinmiyor'
    ];

    // 🔁 Webhook HER ZAMAN gönderilecek
    triggerWebhooks($json);

    echo "<h2> Başarılı</h2>ETTN: <strong>$ettn</strong><br>";
    echo "<a href='images/{$ettn}.png' target='_blank'>Görsel</a><br>";
    echo "<a href='jsonlar/{$ettn}.json' target='_blank'>JSON</a>";
}



    // 👇 Aşağıdaki yollar tools klasörüne göre güncellendi:
    private function convertPdfToPng($pdfPath, $outputBasePath) {
        $pdftoppm = escapeshellcmd(ROOT_DIR . 'tools/pdftoppm.exe');
        $cmd = "$pdftoppm -png " . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputBasePath);
        shell_exec($cmd);
        return $outputBasePath . '-1.png';
    }

    private function readQrFromImage($imagePath) {
        $zbarPath = escapeshellcmd(ROOT_DIR . 'tools/zbarimg.exe');
        $cmd = "$zbarPath -q " . escapeshellarg($imagePath);
        $output = shell_exec($cmd);
        if (!$output) return null;

        preg_match('/QR-Code:(.*)/', $output, $matches);
        return isset($matches[1]) ? trim($matches[1]) : null;
    }

    private function parseQrData($qrData) {
        $parsed = [];
        preg_match_all('/(\w+)=["]?([^"\s]+)["]?/', $qrData, $fields);
        if (!empty($fields[1]) && !empty($fields[2])) {
            foreach ($fields[1] as $i => $key) {
                $parsed[strtolower($key)] = $fields[2][$i];
            }
        }
        return $parsed;
    }
}
