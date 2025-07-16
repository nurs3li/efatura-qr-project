<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hoş Geldiniz</title>
</head>
<body>
    <h1>Merhaba, <?= htmlspecialchars($_SESSION['username']) ?></h1>
    <p>Token: <code><?= htmlspecialchars($_SESSION['token']) ?></code></p>
    <p>Firma Kodu: <strong><?= htmlspecialchars($_SESSION['firma_kodu']) ?></strong></p>

    <a href="logout.php">Çıkış Yap</a>
</body>
</html>
