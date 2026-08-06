---
tags: [sistem, anasayfa]
---

# Manşet Sistemi

Anasayfada beş ayrı manşet bölgesi vardır. Bir içeriğin hangi bölgede
görüneceği, düzenleme ekranındaki **Sayfa Ayarları** kutusundaki
işaretlemelerle belirlenir. İşaretler tek bir post meta dizisinde tutulur.

## Bölgeler

| Bölge | Meta değeri | Sorgu | Görsel şartı |
|---|---|---|---|
| Üst Manşet | `ust_manset` | `index.php:17` | Kendi 1176×330 görseli |
| Sıcak Gündem | `sicak_gundem` | `index.php:76` | Öne çıkarılmış görsel zorunlu |
| Ana Manşet | `manset` | `index.php:123` | Öne çıkarılmış görsel zorunlu |
| Yan Manşet | `yan_manset` | `index.php:179` | Yok |
| Alt Manşet | `alt_manset` | `index.php:448` | Öne çıkarılmış görsel zorunlu |

## İlgili dosyalar

- `inc/theme-settings/class-post-metabox.php:30` — metabox kaydı
- `inc/theme-settings/class-post-metabox.php:147` — arayüz render
- `inc/theme-settings/class-post-metabox.php:684` — kaydetme
- `index.php` — beş sorgu (yukarıdaki tablo)
- `functions.php:6597` — transient temizleme

## Meta anahtarları

- `mevzu_manset_konumlari` (dizi) — işaretli bölge anahtarları
- `ust_manset_gorseli_id`, `ust_manset_gorseli_url` — Üst Manşet görseli

## Önbellek

Manşet sorguları süresiz transient ile saklanır (`set_transient(..., 0)`).
`functions.php:6597`'daki `clear_custom_post_transients()` kayıt sırasında
temizler. **Bu fonksiyon kapsamında olmayan bir post tipi manşete
eklenirse anasayfa güncellenmez.**

## Bağlantılı

[[Resmi İlanlar]] · [[Tema Ayarları]] · [[TTS ve Seslendirme]]
