---
tags: [referans, hook]
---

# Hooklar

Temanın kendi tanımladığı (WP çekirdeğine ait olmayan) action ve filter'lar,
artı sistemler arası bağlantı kuran kritik çekirdek hook'lar. WP'nin
standart `save_post`, `wp_ajax_*` gibi hook'larına birçok yerde bağlanılıyor;
burada yalnızca tema-özel olanlar ve önemli entegrasyon noktaları listelenir.

## Ne işe yarar

"Bu davranışı nereden değiştirebilirim" veya "bu action'ı kim tetikliyor,
kim dinliyor" sorusuna hızlı cevap.

## Özel action'lar

| Hook | Tetiklendiği yer | Dinleyen |
|---|---|---|
| `mevzu_homepage_after_top_promos` | `index.php:5` | `Mevzu_TTS_AI_Manset::render_homepage_bar()` — `inc/tts/class-ai-manset.php:15` (bkz. [[TTS ve Seslendirme]]) |
| `mevzu_tts_generate_audio` | `wp_schedule_single_event()` ile — `inc/tts/class-tts-queue.php:94` | `Mevzu_TTS_Queue::run_job()` — `inc/tts/class-tts-queue.php:24` |
| `mevzu_tts_audio_retention_cleanup` | günlük `wp_schedule_event()` — `inc/tts/class-audio-retention.php:29` | `Mevzu_TTS_Audio_Retention::run_cleanup()` — `inc/tts/class-audio-retention.php:21` |
| `mevzu_theme_update_check_cron` | periyodik zamanlanır — `inc/theme-settings/class-settings-page.php:2204` | `run_theme_update_cron_check()` — `inc/theme-settings/class-settings-page.php:24` |
| `mevzu_theme_auto_apply_scheduled` | `wp_schedule_single_event()` — `inc/theme-settings/class-settings-page.php:2288` | `run_scheduled_auto_theme_apply()` — `inc/theme-settings/class-settings-page.php:25` |
| `fetch_pharmacy_data_event` | kodda devre dışı bırakılmış örnek (`functions.php:2703`, yorum satırı) | — |

## Özel filter'lar

| Hook | Varsayılan/çağrı yeri | Kim override ediyor |
|---|---|---|
| `mevzu_content_width` | `functions.php:517`, varsayılan `776` | — |
| `mevzu_breadcrumb_show_parents` | `functions.php:279` — breadcrumb'da üst kategoriler gösterilsin mi | `inc/resmi-ilanlar/init.php:173` — Resmi İlanlar'da kapatır (bkz. [[Resmi İlanlar]]) |
| `mevzu_meta_degisken` | `functions.php:1845`, varsayılan `'news'` — şema/meta türü | `inc/resmi-ilanlar/init.php:128` — Resmi İlanlar kendi türünü döndürür |

## Yaygın WP hook'ları — kim kullanıyor

`save_post` (yazı kaydında sırayla tetiklenen tema kodları):

- `inc/theme-settings/class-post-metabox.php:14` — Sayfa Ayarları kaydı (öncelik 10)
- `inc/social-automation/class-post-meta.php:22` — sosyal paylaşım ayarı (öncelik 15)
- `inc/tts/init.php:52` — TTS kuyruğu (öncelik **99** — meta'lar kaydedildikten sonra çalışsın diye kasıtlı olarak geç)
- `functions.php:6620` — `clear_custom_post_transients()` (bkz. [[Manşet Sistemi]])
- `functions.php:3263`, `3289` — varsayılan görsel meta bakımı
- `functions.php:7339` — emlak/alsat checkbox kaydı

`before_delete_post`:

- `inc/tts/init.php:53` — ses dosyasını sil
- `inc/theme-settings/class-ads-manager.php:127` — swiper transient temizliği

## Bilinen tuzaklar

- `save_post` sırası önemli: TTS kuyruğu öncelik `99` ile geç çalışır
  çünkü `mevzu_manset_konumlari` (öncelik 10'da kaydedilir) okunmadan
  `yapay_zeka_manset` kontrolü yapılamaz. Yeni bir `save_post` bağlanan
  kod bu meta'ya bağımlıysa önceliğini 10'dan büyük seçmeli, aksi hâlde
  meta henüz kaydedilmeden okunur.
- `mevzu_breadcrumb_show_parents` ve `mevzu_meta_degisken` gibi filtreler
  varsayılan davranışı sessizce değiştirir; bir post tipinde beklenmeyen
  breadcrumb/şema davranışı görülürse önce bu iki filtreye bakılmalı.

## Bağlantılı

[[Manşet Sistemi]] · [[Resmi İlanlar]] · [[TTS ve Seslendirme]] · [[Tema Ayarları]] · [[Meta Anahtarları]]
