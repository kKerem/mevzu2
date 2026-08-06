---
tags: [referans, meta]
---

# Meta Anahtarları

Temanın kullandığı post meta ve option anahtarlarının kapsamlı listesi.
`grep -rn "get_post_meta\|update_post_meta"` ile derlenmiştir; tam liste
değil, sistemlere göre gruplanmış en önemli anahtarları içerir. Sistem
bazlı ayrıntı için ilgili notlara bakın.

## Ne işe yarar

Bu not, "bu meta anahtarını nerede arayacağım" sorusuna hızlı cevap vermek
için var. Her anahtarın tanımlandığı/okunduğu dosya ve satır referanslıdır.

## Sayfa Ayarları kutusu (post meta)

Tümü `inc/theme-settings/class-post-metabox.php` içinde `save_post_meta()`
(satır 637'de başlar) tarafından kaydedilir:

| Anahtar | Amaç | Kayıt satırı |
|---|---|---|
| `mevzu_manset_konumlari` | manşet bölgesi işaretleri (dizi) — bkz. [[Manşet Sistemi]] | `inc/theme-settings/class-post-metabox.php:654` |
| `ust_manset_gorseli_id` / `_url` | Üst Manşet'e özel görsel | `inc/theme-settings/class-post-metabox.php:659`, `669` |
| `yazi_ayarlari` | eski checkbox alanı (dizi); `manset_var` değeri swiper başlıklarını gizler | `inc/theme-settings/class-post-metabox.php:709` |
| `youtube_url` | eski YouTube gömme alanı | `inc/theme-settings/class-post-metabox.php:716` |
| `mevzu_embed_media_url` | genel medya embed URL'si | `inc/theme-settings/class-post-metabox.php:725` |
| `mevzu_native_video_id` / `mevzu_native_video_url` | Cloudflare R2 video | `inc/theme-settings/class-post-metabox.php:733`, `743` |
| `ilan_numarasi` | Resmi İlan numarası — bkz. [[Resmi İlanlar]] | `inc/theme-settings/class-post-metabox.php:760` |
| `reklamlari_gizle` | yazıda reklamları gizle | `inc/theme-settings/class-post-metabox.php:765` |
| `sayfa_renk` | sayfaya özel renk | `inc/theme-settings/class-post-metabox.php:768` |
| `iletisim_formu_eposta` / `iletisim_formu_aktif` | sayfa içi iletişim formu | `inc/theme-settings/class-post-metabox.php:771`, `774` |
| `default_repeater`, `default_repeater_{n}_ilk/ikinci` | tekrarlanan alan grubu | `inc/theme-settings/class-post-metabox.php:782`-`794` |

## TTS (bkz. [[TTS ve Seslendirme]])

| Anahtar | Amaç | Dosya |
|---|---|---|
| `mevzu_tts_job_status`, `mevzu_tts_job_message`, `mevzu_tts_job_queued_at` | kuyruk durumu | `inc/tts/class-tts-queue.php:12` |
| `_mevzu_tts_bg_secret` | arka plan tetikleme tek kullanımlık anahtarı | `inc/tts/class-tts-queue.php:15` |
| `_mevzu_tts_auto_queued_on_publish` | "sadece ilk yayında" kilidi | `inc/tts/init.php:104` |

## Görüntülenme / navigasyon

| Anahtar | Amaç | Dosya |
|---|---|---|
| `views_count` | yazı görüntülenme sayacı | `functions.php:154` (okuma), `functions.php:178` (artırma) |
| `menu-ikon` | menü öğesi ikonu | `inc/theme-settings/class-menu-fields.php:15` |
| `cat_icon` | kategori/menü ikonu (walker) | `inc/class-wp-bootstrap-navwalker.php:280` |
| `gorselDefault` | öne çıkan görsel yoksa varsayılan görsel bayrağı | `functions.php:3259` |
| `ajans` | haber ajansı bilgisi | `functions.php:1865` |
| `ekonomi_kategorisi` | Finans şablonunda seçili kategori | `sablon/finans.php:160` |

## Firma Rehberi (`_firma_*`)

`inc/firma-rehberi/` modülüne ait; adres, telefon, çalışma saatleri,
puanlama, galeri gibi alanlar `_firma_` önekiyle saklanır (ör.
`_firma_lat`, `_firma_lng`, `_firma_rating_total`, `_firma_saatler`).
Ayrıntı için modülün kendi dosyalarına bakın; bu vault'ta ayrı bir not yok.

## Option anahtarları (site geneli)

Post meta değil ama sık karışır — bkz. [[Tema Ayarları]] ve [[Lisans Sistemi]]:

- `options_{anahtar}` — tema ayarları paneli (`inc/theme-settings/class-settings-page.php:2054`)
- `mevzu_modules` — modül aç/kapa durumları
- `mevzu_license_key`, `mevzu_license_cached_status`, `mevzu_site_id` — lisans
- `kkerem_tts_*` (`category_id`, `voice_name`, `update_mode`, `speaking_rate`, `pitch`, ...) — TTS ayarları (`inc/tts/class-admin.php`)
- `mevzu_tts_daily_usage` — TTS günlük kota (`inc/tts/class-daily-limit.php:12`)

## Hook'lar

Bu anahtarların çoğu doğrudan `save_post` içinde kaydedilir; hangi kodun
hangi öncelikte çalıştığı önem taşır (ör. TTS'in `mevzu_manset_konumlari`yı
okuyabilmesi için Sayfa Ayarları kutusundan **sonra** çalışması gerekir).
Tam öncelik sırası ve hook listesi için bkz. [[Hooklar]].

## Bilinen tuzaklar

- `mevzu_manset_konumlari` dizi olarak saklanır (WordPress onu serialize
  eder) ve sorgular `meta_query` + `compare => 'LIKE'` ile yapılır
  (`index.php:22`, `83`, `184`, `455`). Bu, bölge adı başka bir bölge
  adının alt dizesiyse yanlış eşleşmeye yol açabilir: `manset` değeri
  `ust_manset`/`yan_manset`/`alt_manset` içinde de geçtiği için Ana
  Manşet sorgusu değeri tırnaklı arar — `value => '"manset"'`
  (`index.php:130`). Yeni bir manşet bölgesi eklerken bu LIKE
  çakışmasına dikkat edilmeli.
- Alt çizgiyle başlayan anahtarlar (`_mevzu_tts_bg_secret`,
  `_mevzu_tts_auto_queued_on_publish`, `_firma_*`, `_alsat_platform_show`
  gibi) WordPress çekirdeği tarafından "korumalı meta" sayılır ve
  düzenleme ekranındaki genel Özel Alanlar (Custom Fields) kutusunda
  **görünmez**. Bir değeri admin ekranında ararken bulamıyorsanız önce
  anahtarın alt çizgiyle başlayıp başlamadığına bakın.

## Bağlantılı

[[Manşet Sistemi]] · [[Resmi İlanlar]] · [[TTS ve Seslendirme]] · [[Tema Ayarları]] · [[Lisans Sistemi]] · [[Hooklar]]
