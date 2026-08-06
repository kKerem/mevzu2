---
tags: [referans, guvenlik]
---

# Güvenlik Borçları

Bilinen, henüz kapatılmamış konular.

## 1. API anahtarları dağıtılan zip'in içinde

**Durum:** kısmen çözüldü.

Anahtarlar git'ten çıkarıldı (bkz. `inc/keys.php`, `inc/config-keys.php`),
GitHub deposu temiz. Ancak tema bu API'leri **doğrudan** çağırdığı için
gerçek anahtarlar yayın zip'ine enjekte edilir ve müşterinin sunucusunda bulunur.

**Etkilenen anahtarlar:** lisans HMAC imza anahtarı, hava durumu, diyanet,
nöbetçi eczane (4 adet).

**Asıl çözüm:** çağrıların `kkerem.com` üzerinden proxy'lenmesi. Tema yalnızca
kendi lisans anahtarını gönderir, üçüncü taraf anahtarları hiç görmez.
Ayrı bir proje olarak planlanmalı.

## 2. Lisans imza anahtarı ortak

Tüm kurulumlar aynı HMAC anahtarını paylaşır. Bir müşteri anahtarı çıkarırsa
her kurulum adına imza üretebilir. Kurulum başına anahtar üretimi düşünülmeli.

## Bağlantılı

[[Lisans Sistemi]] · [[Tema Haritası]]
