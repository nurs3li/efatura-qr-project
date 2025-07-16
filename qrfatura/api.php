<?php
// Hataları göster (geliştirme ortamı için)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS (dilersen kaldırabilirsin)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Fonksiyon parametresi kontrolü
if (isset($_GET['function'])) {
    $function = $_GET['function'];

    // Controller dosyasını yükle
    require_once __DIR__ . '/api/v1/qrController.php';

    // Sınıfı başlat
    $qr = new qrController();

    // Fonksiyon var mı kontrol et ve çağır
    if (method_exists($qr, $function)) {
        $qr->$function();
    } else {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Fonksiyon bulunamadı: $function"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Lütfen ?function=... parametresi gönderin. Örnek: ?function=uploadDocument"
    ]);
}
