---
tags: [sistem, post-type]
---

# Resmi İlanlar

`resmi-ilanlar` özel post tipi. Basın ilanlarının yayınlanması için kullanılır.

## Kayıt

`inc/resmi-ilanlar/init.php:15` — `register_post_type()`.
Destekler: `title`, `editor`, `page-attributes`, `thumbnail`.
Arşiv açık (`has_archive`), REST açık.

## Davranışlar

- `inc/resmi-ilanlar/init.php:90` — yeni ilan taslağının yayın tarihi otomatik **yarın 00:00** yapılır
- `inc/resmi-ilanlar/init.php:103` — kendi şablonlarını yükler (`templates/single-*`, `templates/archive-*`)
- `inc/resmi-ilanlar/init.php:133` — "İlan Numarası" metabox'ı (`ilan_numarasi` meta)
- `inc/resmi-ilanlar/init.php:167` — breadcrumb'da üst öğeler gizlenir

## Manşet desteği

Manşet alanları (`Sayfa Ayarları`) bu post tipinde de açıktır.
Ayrıntı: [[Manşet Sistemi]].
`yapay_zeka_manset` bu tipte **kapalıdır** — ilan metinleri seslendirmeye uygun değildir.

## Bağlantılı

[[Manşet Sistemi]] · [[Tema Ayarları]]
