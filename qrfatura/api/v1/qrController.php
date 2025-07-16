<?php
session_start(); // 1. Giriş bilgileri için şart

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../functions/qr_helpers.php';

define('ROOT_DIR', realpath(__DIR__ . '/../../') . '/');

class qrController
{
    public function uploadDocument()
    {
        if (!isset($_FILES['qrimage']) || $_FILES['qrimage']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            die("Dosya yüklenemedi.");
        }

        // Giriş yapan kullanıcı veya API üzerinden gelen bilgiler
        $username   = $_POST['username']   ?? ($_SESSION['username'] ?? 'anonim');
        $firma_kodu = $_POST['firma_kodu'] ?? ($_SESSION['firma_kodu'] ?? 'UNKNOWN');
        $token      = $_POST['token']      ?? ($_SESSION['token'] ?? 'no-token');

        // 1. PDF yükle
        $uploadDir = ROOT_DIR . 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $pdfPath = $uploadDir . uniqid('fatura_', true) . '.pdf';
        move_uploaded_file($_FILES['qrimage']['tmp_name'], $pdfPath);

        // 2. PDF -> PNG
        $tempOutputBase = ROOT_DIR . 'images/temp_output';
        $imagePath = $this->convertPdfToPng($pdfPath, $tempOutputBase);
        if (!file_exists($imagePath)) {
            die("PNG oluşturulamadı.");
        }

        // 3. QR oku
        $qrData = $this->readQrFromImage($imagePath);
        if (!$qrData) {
            die("QR kodu okunamadı.");
        }

        // 4. Ayrıştır
        $qrDecoded = json_decode($qrData, true);
        if (!is_array($qrDecoded) || !isset($qrDecoded['ettn'])) {
            die("Geçerli bir ETTN bulunamadı. QR: <pre>$qrData</pre>");
        }

        $ettn = $qrDecoded['ettn'];
        $json = [
            'ettn'        => $ettn,
            'qr_raw'      => $qrData,
            'username'    => $username,
            'firma_kodu'  => $firma_kodu,
            'token'       => $token
        ];

        // 5. Görseli yeniden adlandır
        $finalImage = ROOT_DIR . "images/{$ettn}.png";
        rename($imagePath, $finalImage);

        // 6. JSON kaydet
        $jsonDir = ROOT_DIR . 'jsonlar/';
        if (!is_dir($jsonDir)) mkdir($jsonDir, 0777, true);
        file_put_contents("{$jsonDir}/{$ettn}.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 7. Veritabanına kaydet (önce varsa kontrol et)
        global $pdo;
        $status = 'yeni';

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM faturalar WHERE ettn = :ettn");
            $stmt->execute([':ettn' => $ettn]);
            $exists = $stmt->fetchColumn() > 0;

            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO faturalar (ettn, qr_raw) VALUES (:ettn, :qr_raw)");
                $stmt->execute([':ettn' => $ettn, ':qr_raw' => $qrData]);
            } else {
                $status = 'zaten_kayitli';
            }

        } catch (PDOException $e) {
            die("DB kayıt hatası: " . $e->getMessage());
        }

        // 8. Webhook gönder (her durumda)
        if (function_exists('triggerWebhooks')) {
            $json['status'] = $status;
            triggerWebhooks($json);
        }

        // 9. Başarı cevabı
        echo "<h2>Başarılı</h2>ETTN: <strong>$ettn</strong><br>";
        echo "<p>Status: <strong>$status</strong></p>";
        echo "<p>Kullanıcı: <strong>$username</strong></p>";
        echo "<p>Firma: <strong>$firma_kodu</strong></p>";
        echo "<p>Token: <strong>$token</strong></p>";
        echo "<a href='images/{$ettn}.png' target='_blank'>Görsel</a><br>";
        echo "<a href='jsonlar/{$ettn}.json' target='_blank'>JSON</a>";
    }
    public function deleteDocument()
    {
        if (!isset($_GET['ettn'])) {
            http_response_code(400);
            echo "'ettn' parametresi eksik.";
            return;
        }

        $ettn = $_GET['ettn'];
        $success = true;
        $messages = [];

        global $pdo;
        try {
            $stmt = $pdo->prepare("DELETE FROM faturalar WHERE ettn = :ettn");
            $stmt->execute([':ettn' => $ettn]);
            $messages[] = $stmt->rowCount() > 0 ? "Veritabanından silindi." : "Veritabanında böyle bir ETTN yok.";
        } catch (PDOException $e) {
            $success = false;
            $messages[] = "DB silme hatası: " . $e->getMessage();
        }

        $files = [
            ROOT_DIR . "images/{$ettn}.png",
            ROOT_DIR . "jsonlar/{$ettn}.json",
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                if (unlink($file)) {
                    $messages[] = basename($file) . " silindi.";
                } else {
                    $success = false;
                    $messages[] = basename($file) . " silinemedi.";
                }
            } else {
                $messages[] = basename($file) . " bulunamadı.";
            }
        }

        echo json_encode([
            "status" => $success ? "success" : "error",
            "message" => implode(" | ", $messages)
        ], JSON_UNESCAPED_UNICODE);
    }

    private function convertPdfToPng($pdfPath, $outputBasePath)
    {
        $pdftoppm = escapeshellcmd(ROOT_DIR . 'tools/pdftoppm.exe');
        $cmd = "$pdftoppm -f 1 -l 1 -png -r 300 " . escapeshellarg($pdfPath) . ' ' . escapeshellarg($outputBasePath);
        shell_exec($cmd);
        return $outputBasePath . '-1.png';
    }

    private function readQrFromImage($imagePath)
    {
        $zbarPath = escapeshellcmd(ROOT_DIR . 'tools/zbarimg.exe');
        $cmd = "$zbarPath -q " . escapeshellarg($imagePath);
        $output = shell_exec($cmd);
        if (!$output) return null;

        if (preg_match('/QR-Code:(\{.*\})/s', $output, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
