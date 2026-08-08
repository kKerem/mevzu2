# Değişiklik Günlüğü

Bu dosyanın biçimi [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/)
sözleşmesine dayanır. Sürüm numaraları [Semantic Versioning](https://semver.org/lang/tr/)
kurallarını izler.

Kategoriler: **Eklendi**, **Değiştirildi**, **Düzeltildi**, **Kaldırıldı**.

## [Yayınlanmamış]

### Eklendi

- Haberlere (`post`) tekil, yeniden kullanılabilir bir `Kaynak` alanı
  eklendi. Etiketler gibi çalışır — önceden girilen bir kaynak yazılmaya
  başlanınca otomatik tamamlama ile önerilir — ancak bir yazıya yalnızca
  tek bir kaynak atanabilir (ör. "Karabük Belediyesi"). Düzenleme ekranında
  ana sütunda gösterilir; kaynak atanmışsa tüm tekil haber şablonlarında
  içerikten hemen sonra "Kaynak: X" rozeti olarak da görünür.

### Düzeltildi

- `.gitignore`'daki `vendor/` kuralı kök seviyeye (`/vendor/`) sabitlendi.
  Önceki hali her derinlikte eşleştiği için temanın kendi parçası olan
  `inc/carbon-fields/vendor` (572 dosya) depodan düşmüştü.

## [1.3.8] — 2026-08-07

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
