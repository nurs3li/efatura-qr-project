# e-Fatura QR Project

Bu proje, QR kod ile e-Fatura verilerini okuyup JSON formatında çıkartan ve muhasebe süreçlerine entegre edilmesini sağlayan bir PHP uygulamasıdır.

## 
- QR kod üzerinden e-Fatura verisi okuma
- JSON çıktısı üretme
- PHP ile geliştirildi
- Muhasebe entegrasyonu için uygun yapı
- ## Kullanılan Teknolojiler:
- **PHP**: Ana uygulama dili.
- **zbarimg**: Komut satırı üzerinden QR kodları okuyan harici araç.
- **shell_exec()**: PHP ile `zbarimg` komutunun çalıştırılması için kullanılır.
- **JSON**: Okunan verilerin dışa aktarım formatı.
## KullanımI Hakkında:
1. QR kodu okut
2. JSON çıktısını al
3. Verileri muhasebe yazılımına aktar
##Lisans:
MIT
