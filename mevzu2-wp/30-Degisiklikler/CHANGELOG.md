# Değişiklik Günlüğü

Bu dosyanın biçimi [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/)
sözleşmesine dayanır. Sürüm numaraları [Semantic Versioning](https://semver.org/lang/tr/)
kurallarını izler.

Kategoriler: **Eklendi**, **Değiştirildi**, **Düzeltildi**, **Kaldırıldı**.

## [Yayınlanmamış]

### Eklendi

- Resmi ilanlara manşet alanları eklendi. `Sayfa Ayarları` kutusundan
  işaretlenen resmi ilanlar artık anasayfadaki ilgili manşet bölgesinde
  haberlerle birlikte gösteriliyor. Beş bölgenin tamamı desteklenir;
  `Yapay Zeka Manşeti` resmi ilanlarda kapalıdır.
- Obsidian tabanlı tema bilgi tabanı ve otomatik changelog akışı kuruldu.

### Değiştirildi

- Harici servis anahtarları (lisans imzası, hava durumu, namaz vakitleri,
  nöbetçi eczane) artık `inc/config-keys.php` dosyasından okunuyor. Kendi
  anahtarlarınızı kullanmak isterseniz `wp-config.php` içinde ilgili sabiti
  tanımlamanız yeterli; tanımlıysa o değer önceliklidir.

### Düzeltildi

- Resmi ilan kaydedildiğinde anasayfa manşet önbelleği temizlenmiyordu;
  işaretleme yapılsa bile anasayfa eski içeriği göstermeye devam ediyordu.
