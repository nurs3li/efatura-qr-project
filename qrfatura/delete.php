<?php
require_once __DIR__ . '/config/db.php';
define('ROOT_DIR', realpath(__DIR__) . '/');

if (!isset($_GET['ettn'])) {
    http_response_code(400);
    echo " 'ettn' parametresi gerekiyor.";
    exit;
}

$ettn = trim($_GET['ettn']);
$success = true;
$messages = [];

// Veritabanından sil
try {
    $stmt = $pdo->prepare("DELETE FROM faturalar WHERE ettn = :ettn");
    $stmt->execute([':ettn' => $ettn]);

    if ($stmt->rowCount() > 0) {
        $messages[] = " Veritabanından silindi.";
    } else {
        $messages[] = " ETTN veritabanında bulunamadı.";
        $success = false;
    }
} catch (PDOException $e) {
    $success = false;
    $messages[] = " Veritabanı hatası: " . $e->getMessage();
}

// Dosyaları sil
$files = [
    ROOT_DIR . "images/{$ettn}.png",
    ROOT_DIR . "jsonlar/{$ettn}.json",
    ROOT_DIR . "uploads/fatura1.pdf"
];

foreach ($files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            $messages[] = basename($file) . " silindi.";
        } else {
            $messages[] = basename($file) . " silinemedi.";
        }
    } else {
        $messages[] = basename($file) . " bulunamadı.";
    }
}

echo "<h3>" . implode("<br>", $messages) . "</h3>";
