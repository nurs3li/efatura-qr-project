<?php
$dir = 'jsonlar/';
if (!is_dir($dir)) die('"jsonlar" klasörü bulunamadı.');

$files = array_diff(scandir($dir), ['.', '..']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title> JSON Arşiv</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f2f2f2;
      margin: 40px;
    }
    h2 { color: #333; }
    .card {
      background: white;
      padding: 16px;
      margin-bottom: 12px;
      border-radius: 8px;
      box-shadow: 0 1px 6px rgba(0,0,0,0.1);
    }
    .card h3 {
      margin: 0 0 8px;
      color: #0066cc;
    }
    .meta { font-size: 0.9em; color: #666; }
    a.button {
      display: inline-block;
      margin-top: 10px;
      padding: 6px 12px;
      background: #0066cc;
      color: white;
      border-radius: 4px;
      text-decoration: none;
    }
    a.button:hover { background: #004f99; }
  </style>
</head>
<body>

<h2> JSON Arşivi</h2>

<?php if (!$files): ?>
  <p>Henüz fatura JSON'u bulunmuyor.</p>
<?php else: ?>
  <?php foreach ($files as $file): 
    $path = $dir . $file;
    $data = json_decode(file_get_contents($path), true);
    ?>
    <div class="card">
      <h3> <?= htmlspecialchars($data['no'] ?? $file) ?></h3>
      <div class="meta">
        ETTN: <?= htmlspecialchars($data['ettn'] ?? '-') ?><br>
        Tarih: <?= htmlspecialchars($data['tarih'] ?? '-') ?><br>
        Tutar: <?= htmlspecialchars($data['odenilecek'] ?? $data['vergidahil'] ?? '-') ?> ₺<br>
        Son Güncelleme: <?= date("d.m.Y H:i", filemtime($path)) ?>
      </div>
      <a class="button" href="<?= $path ?>" target="_blank">JSON Görüntüle</a>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<a class="button" href="qrfatura.php">↩ Yeni Fatura Yükle</a>

</body>
</html>
