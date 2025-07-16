<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['token'])) {
    header("Location: login.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>QR Kodlu E-Fatura Yükle</title>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      background: linear-gradient(to right, #00c6ff, #0072ff);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .container {
      background-color: white;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    h2 {
      margin-bottom: 20px;
      color: #333;
    }
    input[type="file"] {
      display: block;
      margin: 20px auto;
      border: 1px solid #ddd;
      padding: 10px;
      border-radius: 10px;
      cursor: pointer;
      width: 100%;
    }
    button {
      background-color: #0072ff;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s;
    }
    button:hover {
      background-color: #005bd3;
    }
    footer {
      margin-top: 20px;
      font-size: 12px;
      color: #888;
    }
  </style>
</head>
<body>
    <div class="container">
        <h3>Merhaba, <?php echo $_SESSION['username']; ?></h3>
        <p><strong>Token:</strong> <?php echo $_SESSION['token']; ?></p>
        <p><strong>Firma:</strong> <?php echo $_SESSION['firma_kodu']; ?></p>
        <p><a href="logout.php">Çıkış Yap</a></p>

        <h2>QR Kodlu E-Fatura Yükle</h2>
        <form action="api.php?function=uploaddocument" method="post" enctype="multipart/form-data">
            <input type="file" name="qrimage" accept=".pdf" required>
            <button type="submit">Yükle ve Oku</button>
        </form>

        <footer>
            <p>© 2025 QR E-Fatura Sistemi</p>
        </footer>
    </div>
</body>

</html>
