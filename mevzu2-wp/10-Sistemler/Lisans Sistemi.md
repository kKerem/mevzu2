---
tags: [sistem, lisans]
---

# Lisans Sistemi

Temanın `lisans.kkerem.com` üzerindeki merkezî sunucuyla HMAC imzalı
istekler üzerinden konuştuğu doğrulama katmanı. Lisans geçersizse hem yönetim
paneli hem de site ön yüzü kilitlenir; [[TTS ve Seslendirme]] gibi API
tüketen modüller de çalışmaz.

## Ne işe yarar

- Lisans anahtarını sunucuya periyodik doğrular, sonucu `transient`/`option`
  olarak önbelleğe alır.
- Lisans geçersizse yönetim panelini ve site ön yüzünü kilitler
  (`wp_die` ile 403 döner).
- Tema güncellemelerini (sürüm listesi, uygulama, geri alma) aynı API
  üzerinden yönetir.
- Diğer Mevzu² AI API çağrıları için paylaşılan bir HMAC anahtarıyla istek imzalar.

## İlgili dosyalar

- `inc/theme-settings/class-license.php:12` — `Mevzu_License` sınıfı
- `inc/theme-settings/class-license.php:15` — `API_URL` (`https://lisans.kkerem.com/api/v1/verify/`)
- `inc/theme-settings/class-license.php:18` — `UPDATE_API_URL`
- `inc/theme-settings/class-license.php:128` — `get_shared_secret()` — HMAC anahtarı (bkz. [[Güvenlik Borçları]])
- `inc/theme-settings/class-license.php:138` — `sign_hmac_payload()`
- `inc/theme-settings/class-license.php:182` — `schedule_license_check()` — periyodik doğrulama
- `inc/theme-settings/class-license.php:197` — `do_license_check()` — sunucuya istek, grace period yönetimi
- `inc/theme-settings/class-license.php:317` — `enforce_license()` — admin kilidi
- `inc/theme-settings/class-license.php:360` — `enforce_license_frontend()` — ön yüz kilidi
- `inc/theme-settings/class-license.php:634` — `apply_theme_version()` — sürüm uygulama/geri alma

## Meta anahtarları

Bu sistem post meta değil, **option** kullanır:

- `mevzu_license_key` — girilen lisans anahtarı (`inc/theme-settings/class-license.php:92`)
- `mevzu_license_cached_status` — sunucudan gelen son durumun önbelleği (`status`, `grace_until`, `checked_at`, `ban_reason`)
- `mevzu_site_id` — siteye özgü, sunucu tarafından tanınan kimlik (`inc/theme-settings/class-license.php:65`)
- `mevzu_license_status` — `TRANSIENT_KEY` (`inc/theme-settings/class-license.php:21`)

## Hook'lar

- `admin_init` → `schedule_license_check()` — 12 saatte bir (`CHECK_INTERVAL = 43200`) doğrulama
- `admin_init` → `enforce_license()` — panel erişimini kısıtlar
- `template_redirect` → `enforce_license_frontend()` — ön yüz erişimini kısıtlar
- `wp_footer` → `render_site_id_in_footer()`
- `wp_ajax_mevzu_verify_license`, `wp_ajax_mevzu_save_license` — elle doğrulama/kaydetme
- `wp_ajax_mevzu_list_versions`, `wp_ajax_mevzu_apply_version` — sürüm yönetimi

## Bilinen tuzaklar

- Sunucuya ulaşılamazsa lisans hemen geçersiz sayılmaz: `GRACE_PERIOD`
  (172800 saniye = 48 saat) boyunca son bilinen "active" durum korunur;
  süre dolunca `inactive`'e döner (`inc/theme-settings/class-license.php:197`, `do_license_check()`).
- `get_shared_secret()` sabit kodlanmış bir yedek değerle (fallback) çalışır;
  `wp-config.php` içine `MEVZU_LICENSE_SHARED_SECRET` tanımlanmazsa tüm
  kurulumlar aynı anahtarı paylaşır — bkz. [[Güvenlik Borçları]].
- Lisans kilitliyken ayarlar sayfası render edilir ama içerik gizlenir
  (`is_locked`); AJAX uçları yine de çağrılabilir, ayrıca yetki kontrolü
  yapılmalı.

## Bağlantılı

[[Güvenlik Borçları]] · [[TTS ve Seslendirme]] · [[Tema Ayarları]]
