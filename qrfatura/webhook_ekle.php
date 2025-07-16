<!-- webhook_ekle.php -->
<form action="webhook_kaydet.php" method="post">
    <label>Webhook URL:</label><br>
    <input type="text" name="url" required><br><br>

    <label>Kullanıcı Adı:</label><br>
    <input type="text" name="kullanici_adi" required><br><br>

    <label>Token (boş bırakırsan otomatik üretilir):</label><br>
    <input type="text" name="token" placeholder="Otomatik oluşturulacak"><br><br>

    <input type="hidden" name="aktif" value="1">

    <button type="submit">Webhook Kaydet</button>
</form>
