<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

session_start();

// Giriş kontrolü
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Silme işlemi
if (isset($_GET['sil'])) {
    $id = intval($_GET['sil']);
    try {
        $stmt = $pdo->prepare("DELETE FROM webhooklar WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: webhook_listele.php");
        exit;
    } catch (PDOException $e) {
        echo "Silme hatası: " . $e->getMessage();
        exit;
    }
}

// Webhookları çek
try {
    $stmt = $pdo->query("SELECT * FROM webhooklar ORDER BY eklenme_tarihi DESC");
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Webhook Listesi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 30px;
        }
        h2 {
            margin-bottom: 10px;
        }
        .btn {
            padding: 6px 12px;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-sil {
            background-color: #e74c3c;
        }
        .btn-ekle {
            background-color: #27ae60;
            float: right;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px #ccc;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #0066cc;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .no-data {
            text-align: center;
            font-style: italic;
            color: #777;
        }
    </style>
</head>
<body>

    <h2>📡 Webhook Kayıtları
        <a href="webhook_ekle.php" class="btn btn-ekle">+ Yeni Webhook</a>
    </h2>

    <?php if (empty($webhooks)): ?>
        <p class="no-data">Henüz kayıtlı webhook bulunmamaktadır.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>URL</th>
                <th>Kullanıcı</th>
                <th>Token</th>
                <th>Aktif</th>
                <th>Tarih</th>
                <th>İşlem</th>
            </tr>

            <?php foreach ($webhooks as $row): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['url']) ?></td>
                    <td><?= htmlspecialchars($row['kullanici_adi'] ?? 'anonim') ?></td>
                    <td><?= htmlspecialchars($row['token']) ?></td>
                    <td><?= $row['aktif'] ? '✅' : '❌' ?></td>
                    <td><?= $row['eklenme_tarihi'] ?></td>
                    <td>
                        <a href="?sil=<?= $row['id'] ?>" class="btn btn-sil"
                           onclick="return confirm('Bu webhook kaydını silmek istediğinize emin misiniz?')">Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</body>
</html>
