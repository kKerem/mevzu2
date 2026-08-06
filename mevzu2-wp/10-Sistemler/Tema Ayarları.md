---
tags: [sistem, yonetim-paneli]
---

# Tema Ayarları

Eskiden ACF (Advanced Custom Fields) eklentisiyle yönetilen tema seçenekleri,
artık `inc/theme-settings/` altındaki native bir panelle yönetiliyor. Panel
hem genel görünüm ayarlarını hem de hangi modüllerin (TTS, resmi ilanlar,
firma rehberi vb.) aktif olduğunu kontrol eder.

## Ne işe yarar

- **Genel/Header/Anasayfa/Footer/İçerik/Arşiv/Sosyal Medya/Video Depolama**
  sekmeleriyle site genelindeki seçenekleri AJAX ile kaydeder.
- **Modül Yöneticisi**: TTS, Üyelik, Resmi İlanlar, Firma Rehberi gibi
  özellikleri tek tek açıp kapatır.
- Tüm ayar okumaları tek bir yardımcı fonksiyon üzerinden yapılır:
  `get_opt_g($option_name, $key, $default)`.

## İlgili dosyalar

- `functions.php:6413` — `get_opt_g()` tanımı (ayar okuma katmanı)
- `inc/theme-settings/init.php:3` — panelin giriş noktası (ACF'nin yerini alan native altyapı)
- `inc/theme-settings/init.php:37` — `mevzu_register_modules()` — tüm modüllerin listesi
- `inc/theme-settings/class-settings-page.php:11` — ana ayar sayfası sınıfı
- `inc/theme-settings/class-settings-page.php:2054` — `ajax_save()` — form kaydı
- `inc/theme-settings/class-module-manager.php:10` — modül aç/kapa altyapısı
- `inc/theme-settings/class-post-metabox.php` — yazı bazlı "Sayfa Ayarları" kutusu (bkz. [[Manşet Sistemi]])
- `inc/theme-settings/compat.php` — eski ACF alan adlarıyla geriye dönük uyumluluk

## Meta anahtarları

Bu sistem çoğunlukla **option** (site geneli) kullanır, post meta değil:

- `options_{anahtar}` — her tekil ayar bu önekle saklanır
  (`inc/theme-settings/class-settings-page.php:2054`, dizi olmayan değerler için)
- `mevzu_modules` (dizi) — modül aç/kapa durumları
  (`inc/theme-settings/class-module-manager.php:61`, `set_active()`)

`get_opt_g('options', 'site_rengi')` gibi çağrılar önce
`options_site_rengi` tekil seçeneğine, yoksa `options` dizisi içindeki
`site_rengi` anahtarına bakar.

## Hook'lar

- `admin_menu` → `Mevzu_Settings_Page::add_menu_pages()` (`inc/theme-settings/class-settings-page.php:14`)
- `wp_ajax_mevzu_save_settings` → `ajax_save()` — ana form kaydı
- `wp_ajax_mevzu_save_blocks`, `wp_ajax_mevzu_save_ana_kat_blocks` — anasayfa blok ayarları
- `mevzu_theme_update_check_cron` (özel action, periyodik zamanlanır) → güncelleme kontrolü
- `mevzu_theme_auto_apply_scheduled` (özel action) → otomatik sürüm uygulama
- `mevzu_content_width` (filter, `functions.php:518`) → tema içerik genişliği
- `mevzu_breadcrumb_show_parents`, `mevzu_meta_degisken` (filter) — diğer modüllerin
  (ör. [[Resmi İlanlar]]) çekirdek davranışı override etmesini sağlar

## Bilinen tuzaklar

- `get_opt_g()` önce `{option_name}_{key}` tekil seçeneğine bakar; eski
  `options_manset` gibi dizi tabanlı kayıtlarla aynı anahtar çakışırsa
  tekil kayıt her zaman kazanır (fonksiyonun kendi yorum satırında da
  belirtilmiş, `functions.php:6415` civarı).
- Bir modül `Mevzu_Module_Manager::register()` ile kayıtlı değilse ayarlar
  panelinde hiç görünmez; sadece `mevzu_modules` seçeneğine elle `1` yazmak
  yetmez.
- Ayarlar sayfası lisans geçersizken kilitlenir (`is_locked`) — bkz.
  [[Lisans Sistemi]].

## Bağlantılı

[[Lisans Sistemi]] · [[Manşet Sistemi]] · [[Resmi İlanlar]] · [[Meta Anahtarları]]
