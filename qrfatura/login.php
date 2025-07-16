<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['username'] = $_POST['username'] ?? 'anonim';
    $_SESSION['token'] = $_POST['token'] ?? 'no-token';
    $_SESSION['firma_kodu'] = $_POST['firma_kodu'] ?? 'BILINMIYOR';

    header("Location: qrfatura.php"); // giriş sonrası yönlendirme
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap</title>
    <style>
        body { font-family: Arial; background-color: #f5f5f5; padding: 50px; }
        form { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        input[type="text"] { width: 100%; padding: 10px; margin-bottom: 15px; }
        input[type="submit"] { padding: 10px 20px; background-color: #0066cc; color: white; border: none; border-radius: 5px; }
        h2 { text-align: center; }
    </style>
</head>
<body>

<form method="POST">
    <h2>Giriş Yap</h2>
    <label>Kullanıcı Adı:</label>
    <input type="text" name="username" required>

    <label>Token:</label>
    <input type="text" name="token" required>

    <label>Firma Kodu:</label>
    <input type="text" name="firma_kodu" required>

    <input type="submit" value="Giriş Yap">
</form>

</body>
</html>
