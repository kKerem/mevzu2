---
tags: [sistem, tts]
---

# TTS ve Seslendirme

Haber metinlerini Google Cloud TTS ile sese çeviren, eskiden ayrı bir eklenti
(`kkerem-text-to-speech`) olan, artık tema özelliği olarak `inc/tts/` altında
çalışan modül. Yönetim panelinde **Mevzu² AI** adıyla görünür ve
[[Lisans Sistemi]] üzerinden yetkilendirilir. Anasayfadaki "Günün Özeti"
çubuğu ve tekil yazı sayfasındaki ses oynatıcı bu modülün ürettiği dosyaları
kullanır.

## Ne işe yarar

- Hedef kategorideki (`kkerem_tts_category_id`) yayınlanan yazılar için
  otomatik olarak seslendirme kuyruğa alınır.
- Bir yazı, hedef kategoride olmasa da **Yapay Zeka Manşeti** (`Sayfa Ayarları`
  kutusundaki `yapay_zeka_manset` işareti) ile de seslendirme kapsamına girebilir.
- Anasayfada `mevzu_homepage_after_top_promos` hook'una bağlı bir "Günün
  Özeti" çubuğu, o gün seslendirilen yazıları oynatılabilir satırlar hâlinde listeler.
- Toplu üretim ekranı (Bulk Generator) geçmiş yazılar için ses dosyası olmayanları tarayıp topluca kuyruğa alır.

## İlgili dosyalar

- `inc/tts/init.php:36` — `Mevzu_TTS` ana sınıfı, `save_post`/`before_delete_post` bağlanır
- `inc/tts/init.php:52` — `add_action('save_post', ..., 99, 2)` — meta kaydından sonra kuyruk tetikleyici
- `inc/tts/class-tts-helpers.php:33` — `mevzu_tts_post_has_yapay_zeka_manset()`
- `inc/tts/class-tts-helpers.php:41` — `mevzu_tts_post_should_process()` — hedef kategori veya YZ manşeti kontrolü
- `inc/tts/class-tts-helpers.php:126` — `mevzu_tts_get_todays_yapay_zeka_playlist()` — günün oynatma listesi
- `inc/tts/class-tts-queue.php:23` — kuyruk hook kaydı (`mevzu_tts_generate_audio`)
- `inc/tts/class-tts-queue.php:157` — `run_job()` — asenkron üretim
- `inc/tts/class-tts-service.php:21` — `synthesize_text()` — Google Cloud TTS çağrısı
- `inc/tts/class-file-manager.php:43` — ses dosyası yolu (`wp-content/uploads/kkerem-tts/post-{ID}.mp3`)
- `inc/tts/class-daily-limit.php:10` — günlük kullanım kotası (sunucu ile senkron)
- `inc/tts/class-audio-retention.php:17` — eski ses dosyalarını temizleyen günlük cron
- `inc/tts/class-ai-manset.php:14` — "Günün Özeti" çubuğu ve modal render (constructor)
- `inc/tts/class-bulk-generator.php:11` — toplu üretim admin ekranı (`KKEREM_TTS_Bulk_Generator`)
- `inc/tts/class-admin.php:11` — ayarlar sayfası (ses, hız, perde, güncelleme modu) (`KKEREM_TTS_Admin`)

## Meta anahtarları

- `mevzu_manset_konumlari` (dizi) — `yapay_zeka_manset` değeri bu diziye eklenirse yazı seslendirme kapsamına girer ([[Manşet Sistemi]] ile ortak)
- `mevzu_tts_job_status`, `mevzu_tts_job_message`, `mevzu_tts_job_queued_at` — kuyruk durumu (`inc/tts/class-tts-queue.php:12`)
- `_mevzu_tts_bg_secret` — arka plan tetikleme isteği için tek kullanımlık anahtar
- `_mevzu_tts_auto_queued_on_publish` — "sadece ilk yayında" modunda tekrar kuyruğa almayı engeller (`inc/tts/init.php:104`)

## Hook'lar

- `save_post` → `Mevzu_TTS::handle_save_post_for_tts()` (öncelik 99 — manşet meta'sı kaydedildikten sonra çalışsın diye)
- `before_delete_post` → ses dosyasını da siler
- `mevzu_tts_generate_audio` (özel action, `wp_schedule_single_event` ile tetiklenir) → `Mevzu_TTS_Queue::run_job()`
- `mevzu_tts_audio_retention_cleanup` (özel action, günlük `wp_schedule_event`) → eski dosya temizliği
- `mevzu_homepage_after_top_promos` (tema hook'u, `index.php:5`'te tetiklenir) → `Mevzu_TTS_AI_Manset::render_homepage_bar()`
- `wp_ajax_mevzu_yz_run_tts_queue` / `wp_ajax_nopriv_...` — kuyruğu arka planda çalıştıran AJAX uç noktası

## Bilinen tuzaklar

- Kota `Mevzu_TTS_Daily_Limit` sunucu tarafıyla senkron çalışır; lisans
  geçersizse (`Mevzu_AI_Client::is_ready()` false döner) kuyruk hiç
  oluşmaz, hata da görünmeyebilir — önce [[Lisans Sistemi]] durumuna bakın.
- `kkerem_tts_update_mode` seçeneği `publish_only` ise bir yazı yalnızca
  **ilk yayınlandığında** kuyruğa girer; sonraki düzenlemelerde
  `_mevzu_tts_auto_queued_on_publish` meta'sı zaten `1` olduğu için tekrar
  seslendirme üretilmez.
- Ses dosyaları veritabanında değil dosya sisteminde tutulur
  (`wp-content/uploads/kkerem-tts/post-{ID}.mp3`); yedekleme veya taşıma
  sırasında bu klasörün de kopyalanması gerekir.

## Bağlantılı

[[Manşet Sistemi]] · [[Lisans Sistemi]] · [[Güvenlik Borçları]]
