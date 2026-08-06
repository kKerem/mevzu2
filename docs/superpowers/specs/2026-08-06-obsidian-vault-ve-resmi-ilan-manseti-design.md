# Obsidian Vault Entegrasyonu ve Resmi İlanlara Manşet Alanları

**Tarih:** 2026-08-06
**Proje:** mevzu2 WordPress teması
**Durum:** Onaylandı

---

## Genel Bakış

Dört iş tek spec altında toplanmıştır:

- **Bölüm A** — Tema için Obsidian tabanlı bilgi tabanı, git temeli ve otomatik changelog akışı.
- **Bölüm B** — `resmi-ilanlar` post tipine, haberlerde (`post`) mevcut olan "Sayfa Ayarları" manşet alanlarının kazandırılması.
- **Bölüm C** — Temada sabit kodlanmış API anahtarlarının git dışına alınması (depo herkese açık olacağı için ön koşul).
- **Bölüm D** — GitHub bağlantısı ve sürüm yayınlama akışı (changelog mühürleme → sürüm artırma → zip → sunucuya yükleme).

**Uygulama sırası: A → C → B → D.**

Gerekçe: A git temelini kurar. C ilk push'tan **önce** tamamlanmalıdır, aksi halde anahtarlar public depoya girer. B ürün özelliğidir ve ilk anlamlı changelog kaydını üretir. D yayın akışını bağlar ve kısmen SSH'a bağımlıdır (bkz. D3).

---

## Mevcut Durum Tespiti

Tasarım öncesi kodda doğrulanan gerçekler:

| Bulgu | Konum |
|---|---|
| Obsidian vault mevcut, içi boş (yalnızca `Welcome.md`) | `mevzu2-wp/` |
| Bilgi grafiği daha önce üretilmiş (2026-07-08) | `graphify-out/GRAPH_REPORT.md`, `manifest.json` |
| Git deposunun kökü tema değil, bir üstü: `wp-content/themes/` | — |
| Üst depoda commit/dal/etiket/uzak sunucu/stash yok — devralınacak geçmiş yok | — |
| Depo kökünde temayla ilgisiz projeler var: `lisans.kkerem.com/`, `dosya_lisans.kkerem.com/`, `mevzu2*.zip` yedekleri | `wp-content/themes/` |
| Mevcut `.gitignore` tek satırdan ibaret (`node_modules`) | `wp-content/themes/.gitignore` |
| "Sayfa Ayarları" metabox'ı yalnızca `'post'` için kayıtlı | `inc/theme-settings/class-post-metabox.php:30-37` |
| Kaydetme generic `save_post` hook'unda, nonce korumalı → post tipinden bağımsız çalışır | `inc/theme-settings/class-post-metabox.php:14`, `:637-674` |
| Beş anasayfa manşet sorgusunun tamamı `'post_type' => 'post'` ile sabitlenmiş | `index.php:17`, `:76`, `:123`, `:179`, `:448` |
| Üç sorgu ek olarak `_thumbnail_id EXISTS` şartı koşuyor | `index.php:84`, `:131`, `:456` |
| `index.php` içinde manşetle ilgisiz 9 adet daha `'post_type' => 'post'` var — karıştırılmamalı | `index.php:265`, `:412`, `:543`, `:600`, `:671`, `:797`, `:831`, `:879` |
| Transient temizleme `post` dışındaki tipte erken `return` ediyor | `functions.php:6602` |
| Manşet kartları yalnızca `the_post_thumbnail()` + `the_title()` kullanıyor, kategori rozeti yok | `index.php:144-157` |
| Üst Manşet görsel alanı `#manset_konum_ust_manset` checkbox'ına bağlı (kategoriye değil) | `inc/theme-settings/class-post-metabox.php:380-433` |
| `render_ust_manset_gorsel()` kategoriye bağlı eski bir renderer; hiçbir metabox onu kaydetmiyor (ölü kod) | `inc/theme-settings/class-post-metabox.php:434` |
| `resmi-ilanlar` post tipi `thumbnail` desteğine sahip | `inc/resmi-ilanlar/init.php:58` |

---

## Bölüm A — Obsidian + Git + Changelog

### A1. Git temeli

Changelog otomasyonu git commit'lerine dayanacaktır (kullanıcı kararı).

**Depo kökü temaya taşınır.** Şu an kök `wp-content/themes/` — içinde `lisans.kkerem.com/`, `dosya_lisans.kkerem.com/` gibi ilgisiz projeler ve zip yedekleri var. Depo `mevzu2/` tema klasörüne alınır ki changelog ve sürüm numarası doğrudan temayı anlatsın, yayınlanan zip ile depo bire bir örtüşsün.

Taşıma adımları:

1. Üst depoda yalnızca bu spec'in commit'i var — dal, etiket, uzak sunucu ve stash yok. **Devralınacak geçmiş bulunmadığı** için `wp-content/themes/.git` silinir. (Uygulama sırasında bu bir kez daha `git reflog` ile teyit edilmelidir; beklenmedik bir commit çıkarsa silme yapılmaz, kullanıcıya sorulur.)
2. `wp-content/themes/.gitignore` (tek satır: `node_modules`) olduğu yerde bırakılır — ilgisiz projelere ait, dokunulmaz.
3. `mevzu2/` içinde `git init` çalıştırılır.
4. Aşağıdaki `.gitignore` yazılır ve tema tek bir başlangıç commit'i olarak kaydedilir. Bu spec dosyası da o commit'e dahil olur.

**`.gitignore`** oluşturulur (artık yollar tema köküne göredir). Depo dışında tutulacaklar:

```
.DS_Store
node_modules/
*.zip
*.psd
package-lock.json
graphify-out/cache/
graphify-out/graph.json      # 10 MB üretilmiş dosya
graphify-out/graph.html      # 563 KB üretilmiş dosya
mevzu2-wp/.obsidian/workspace.json
mevzu2-wp/.obsidian/workspace-mobile.json
```

`mevzu2-wp/` vault'unun **kendisi depoya dahildir** — notlar tema ile birlikte versiyonlanır. Yalnızca Obsidian'ın kişisel arayüz durumu (`workspace*.json`) hariç tutulur.

Ardından mevcut tema tek bir başlangıç commit'i olarak kaydedilir. Bu commit changelog'a girmez; changelog bu noktadan sonrasını anlatır.

### A2. Vault yapısı

`mevzu2-wp/` içinde dört klasör:

```
00-Genel/
  Tema Haritası.md          # giriş noktası, tüm sistemlere bağlanır
  Sürüm Notları.md          # yayınlanmış sürümlerin özeti
10-Sistemler/
  Manşet Sistemi.md
  Resmi İlanlar.md
  TTS ve Seslendirme.md
  Sosyal Otomasyon.md
  Tema Ayarları.md
  Üyelik Sistemi.md
  Firma Rehberi.md
  Haber Botu.md
  ...                       # graphify çıktısından türetilen diğer sistemler
20-Referans/
  Meta Anahtarları.md
  Hooklar.md
  Tema Seçenekleri.md
  Şablon Dosyaları.md
30-Degisiklikler/
  CHANGELOG.md
```

**Sistem notu iskeleti** (her `10-Sistemler/` notu bu yapıyı taşır):

1. **Ne işe yarar** — bir paragraf, kod bilmeyen birinin anlayacağı dilde
2. **İlgili dosyalar** — `dosya.php:satır` biçiminde, tıklanabilir referanslar
3. **Meta anahtarları** — sistemin okuduğu/yazdığı `post_meta` ve `option` anahtarları
4. **Hook'lar** — bağlandığı `add_action` / `add_filter` noktaları
5. **Bağlantılı sistemler** — `[[Manşet Sistemi]]` biçiminde wiki-link

Hammadde olarak `graphify-out/GRAPH_REPORT.md` ve `manifest.json` kullanılır; kod sıfırdan yeniden taranmaz.

**Neden dosya başına değil sistem başına:** temada 423 PHP dosyası var. Dosya başına not, graph view'i okunamaz hale getirir ve her düzenlemede not bakımı gerektirir. Sistem bazlı yapı hem gezilebilir kalır hem de bakım maliyeti düşüktür.

### A3. Otomatik senkronizasyon

`.claude/settings.json` içine bir **Stop hook** kurulur. Bir iş tamamlandığında sırasıyla:

1. `git diff --name-only` ile değişen dosyalar tespit edilir
2. Değişen dosyalar `10-Sistemler/` notlarıyla eşleştirilir, etkilenen not güncellenir
3. `30-Degisiklikler/CHANGELOG.md` içinde `## [Yayınlanmamış]` başlığı altına bir satır eklenir
4. Değişiklikler tek commit olarak kaydedilir

**CHANGELOG biçimi** — [Keep a Changelog](https://keepachangelog.com/tr/) sözleşmesi, Türkçe başlıklarla:

```markdown
# Değişiklik Günlüğü

## [Yayınlanmamış]

### Eklendi
- Resmi ilanlara manşet alanları eklendi; ilanlar artık anasayfada
  haberlerle birlikte manşette gösterilebiliyor.

### Düzeltildi
- Resmi ilan kaydedildiğinde anasayfa manşet önbelleği temizlenmiyordu.

## [2.3.0] — 2026-07-08
...
```

Kategoriler: `Eklendi`, `Değiştirildi`, `Düzeltildi`, `Kaldırıldı`.

**Sürüm çıkarma akışı:** yeni sürüm yayınlanacağı zaman `[Yayınlanmamış]` bloğu `## [2.4.0] — 2026-08-15` olarak mühürlenir, `style.css` içindeki `Version:` alanı aynı numaraya çekilir ve yeni boş bir `[Yayınlanmamış]` bloğu açılır. Böylece yayın metni her an hazır bekler.

**Bilinen sonuç:** hook her iş bitiminde commit attığı için commit sayısı normalden fazla olur. Bu istenmezse hook "changelog'u yaz, commit'i kullanıcı onayına bırak" moduna alınabilir — bu, sonradan tek satırlık bir ayar değişikliğidir.

---

## Bölüm B — Resmi İlanlara Manşet Alanları

### Amaç

`resmi-ilanlar` post tipini düzenleme ekranında, haberlerdekiyle aynı "Sayfa Ayarları" kutusu görünecek. İşaretlenen manşet konumu anasayfada gerçekten devreye girecek.

### Kapsam kararları

- **Açılacak alanlar:** `ust_manset`, `sicak_gundem`, `manset`, `yan_manset`, `alt_manset` — yani beş manşet konumunun tamamı.
- **Açılmayacak alan:** `yapay_zeka_manset`. Bu alan TTS ile haber seslendirip anasayfadaki "Günün Özeti" bölümüne ekliyor; resmî ilan metinlerinde anlamlı bir çıktı üretmez.
- **Görsel şartı:** mevcut davranış korunur. Öne çıkarılmış görseli olmayan ilan manşette görünmez. Bunun yerine metabox'ta açık bir uyarı gösterilir. Varsayılan görsel veya üretilmiş başlık kartı yapılmaz.

### B1. Metabox'ı `resmi-ilanlar` için kaydet

**Dosya:** `inc/theme-settings/class-post-metabox.php:30-37`

`add_meta_box()` çağrısının dördüncü argümanı `'post'` yerine `[ 'post', 'resmi-ilanlar' ]` olur. Başka bir değişiklik gerekmez; aynı metabox, aynı arayüz, aynı konum (`side`, `high`).

Not: aynı dosyadaki `mevzu-native-video`, `mevzu-sosyal-gonderi` metabox'ları `'post'` olarak kalır — kapsam dışıdır.

### B2. Render tarafında post tipine göre ayrım

**Dosya:** `inc/theme-settings/class-post-metabox.php:147` (`render_sayfa_ayarlari`)

İki ekleme:

1. **Yapay Zeka Manşeti bloğu gizlenir.** Satır 245'teki mevcut koşula `'resmi-ilanlar' !== $post->post_type` şartı eklenir. Blok zaten TTS modülü ve kategori koşullarına bağlı; bu yalnızca bir şart daha ekler.

2. **Görsel uyarısı eklenir.** Manşet checkbox'larının altına, öne çıkarılmış görsel yokken ve en az bir manşet konumu işaretliyken görünen bir uyarı satırı:

   > Manşette görünmesi için öne çıkarılmış görsel eklemelisiniz.

   Uyarı yalnızca bilgilendiricidir — kaydetmeyi engellemez. (Yapay Zeka Manşeti'ndeki `lockPostSaving` davranışı buraya taşınmaz; resmî ilanın görselsiz kaydedilmesi meşru bir durumdur, yalnızca manşette çıkmaz.)

   Uyarı görsel gerektiren üç konum (`manset`, `sicak_gundem`, `alt_manset`) için gösterilir. `ust_manset` kendi ayrı 1176×330 görselini kullandığı, `yan_manset` ise `_thumbnail_id` şartı koşmadığı için uyarı kapsamı dışındadır.

Üst Manşet görsel yükleme alanı (satır 380-433) değişiklik gerektirmez — `#manset_konum_ust_manset` checkbox'ının durumuna bağlı çalışır, kategoriye bakmaz.

### B3. Kaydetme tarafında izinli alan listesi

**Dosya:** `inc/theme-settings/class-post-metabox.php:637-674` (`save_post_meta`)

Kaydetme generic `save_post` hook'unda olduğu ve nonce ile korunduğu için `resmi-ilanlar` kaydında **zaten çalışır**. Tek ekleme: satır 646'daki `$allowed` dizisinden, post tipi `resmi-ilanlar` ise `yapay_zeka_manset` çıkarılır. Böylece istemci tarafı manipülasyonuyla bu alan resmî ilana yazılamaz.

### B4. Anasayfa sorgularını aç

**Dosya:** `index.php`

Beş sorguda `'post_type' => 'post'` → `'post_type' => [ 'post', 'resmi-ilanlar' ]`:

| Satır | Bölüm | Doğrulama ipucu (aynı sorgunun `meta_query` satırı) | Ek şart |
|---|---|---|---|
| 17 | Üst Manşet | `:22` → `'ust_manset'` | — |
| 76 | Sıcak Gündem | `:83` → `'sicak_gundem'` | `_thumbnail_id EXISTS` (`:84`) |
| 123 | Ana Manşet | `:130` → `'"manset"'` | `_thumbnail_id EXISTS` (`:131`) |
| 179 | Yan Manşet | `:184` → `'yan_manset'` | — |
| 448 | Alt Manşet | `:455` → `'alt_manset'` | `_thumbnail_id EXISTS` (`:456`) |

**Dikkat:** `index.php` içinde manşetle ilgisi olmayan başka 9 `'post_type' => 'post'` satırı daha var (`:265`, `:412`, `:543`, `:600`, `:671`, `:718` hariç, `:797`, `:831`, `:879`). Toplu değiştirme yapılmamalı; her sorgu yukarıdaki `meta_query` ipucuyla teyit edilerek tek tek düzenlenmelidir.

Sıralama (`date DESC`) ve adet ayarları değişmez; işaretlenen ilan haberlerle aynı havuzda tarih sırasına girer.

`archive.php` içindeki `mevzu2_archive_manset_base_query_args()` kategori arşivlerine aittir, kapsam dışıdır.

### B5. Transient temizlemeyi kapsama al

**Dosya:** `functions.php:6596-6620` (`clear_custom_post_transients`)

Satır 6602'deki erken çıkış:

```php
if (get_post_type($post_id) !== 'post') {
    return;
}
```

şu hale gelir:

```php
if ( ! in_array( get_post_type( $post_id ), [ 'post', 'resmi-ilanlar' ], true ) ) {
    return;
}
```

**Bu adım atlanırsa özellik çalışmaz:** manşet sorguları süresiz transient ile cache'leniyor (`index.php:135`, `set_transient(..., 0)`). Temizleme olmadan işaretleme yapılsa bile anasayfa eski sonucu göstermeye devam eder.

### B6. Şablon değişikliği gerekmez

Manşet kartları yalnızca `the_post_thumbnail()` ve `the_title()` kullanıyor (`index.php:144-157`). Kategori rozeti, yazar bilgisi gibi `post`'a özgü alan yok. Resmî ilanlar mevcut kartlarda sorunsuz render olur.

---

## Bölüm C — Sırların Git Dışına Alınması

Depo herkese açık olacağı için, temada sabit kodlanmış API anahtarları git'e girmeden ayıklanır.

### C0. Tespit edilen sırlar

| Konum | Ne | Sızarsa |
|---|---|---|
| `inc/theme-settings/class-license.php:132` | Lisans HMAC imza anahtarı (64 hane hex) | **En kritik.** Lisans doğrulama istekleri imzalanabilir, koruma aşılır |
| `page-nobetci-eczaneler.php:84-87` | 4 adet eczane API anahtarı (rotasyonlu) | Kota tüketimi |
| `functions.php:6228` | Hava durumu API anahtarı | Kota tüketimi |
| `functions.php:4152` | `diyanet.kkerem.com` anahtarı (haftalık 500 sorgu) | Kendi servisinin kotası tükenir |

**GitHub deposu doğrulandı:** `kKerem/mevzu2` public, ancak yalnızca `LICENSE` ve `README.md` içeriyor (3 commit, hepsi bu iki dosyaya ait). Tema kaynağı hiç push edilmemiş. Bu nedenle **anahtarlar sızmamıştır ve döndürülmelerine gerek yoktur** — ilk push'tan önce ayıklanmaları yeterlidir.

### C1. Yapı

- `inc/config-keys.php` — gerçek değerler, `.gitignore`'da
- `inc/config-keys.sample.php` — aynı yapı, boş değerler, depoda
- Okuma sırası: `wp-config.php` sabiti → `config-keys.php` → boş dize

Sabit tanımlıysa müşteri kendi anahtarını `wp-config.php`'den verebilir; vermezse zip ile gelen değer kullanılır. Mevcut çağrı noktalarının mantığı değişmez, yalnızca değerin nereden geldiği değişir.

`class-license.php:132`'deki gömülü fallback silinir. `MEVZU_LICENSE_SHARED_SECRET` mekanizması zaten yazılmış durumda (satır 128-131); yalnızca fallback kaynağı `config-keys.php`'ye çevrilir.

### C2. Bilinen sınır

Yayın script'i gerçek `config-keys.php`'yi zip'e enjekte eder, çünkü tema bu API'leri doğrudan çağırır. Anahtarlar müşterinin elinde olmaya devam eder. **Bu adım GitHub'ı temiz tutar, dağıtılan zip'i değil.** Asıl çözüm çağrıları `kkerem.com` üzerinden proxy'lemek olurdu; ayrı bir proje olarak kapsam dışıdır ve vault'ta `20-Referans/Güvenlik Borçları.md` notuna yazılır.

---

## Bölüm D — Sürüm Yayınlama Akışı

### D1. GitHub bağlantısı

- Uzak depo: `https://github.com/kKerem/mevzu2` (public, varsayılan dal `main`)
- `gh` CLI kurulu ve `kKerem` hesabıyla yetkili — ek kimlik doğrulama gerekmez
- Yerel dal `master` → `main` olarak yeniden adlandırılır
- Uzaktaki 3 commit (`LICENSE`, `README.md`) **korunur**; tema bunların üzerine eklenir. Depo geçmişi ve URL bozulmaz, force-push yapılmaz.

### D2. `/surum-cikar <versiyon>` komutu

Sırayla:

1. `CHANGELOG.md`'deki `[Yayınlanmamış]` bloğu `## [1.3.8] — YYYY-AA-GG` olarak mühürlenir, yerine yeni boş blok açılır
2. `style.css` içindeki `Version:` alanı yeni sürüme çekilir
3. Commit + `v1.3.8` etiketi + `main` dalına push
4. Zip paketlenir: `.git/`, `docs/`, `graphify-out/`, `node_modules/`, `mevzu2-wp/`, `*.psd`, `*.zip` hariç tutulur; gerçek `inc/config-keys.php` enjekte edilir
5. Sunucuya yüklenir: `/home/kkerem/web/lisans.kkerem.com/public_html/maya/<versiyon>/mevzu2.zip`
6. Yükleme doğrulanır (dosya mevcut mu, boyut yerel zip ile eşleşiyor mu), ardından GitHub Release oluşturulur

Adım 6'daki doğrulama başarısız olursa Release **oluşturulmaz** — yarım yayın durumu engellenir.

### D3. SSH erişimi — şu an engelli

Hedef sunucu: `104.143.0.74:22`, yol `/home/kkerem/web/lisans.kkerem.com/public_html/maya/`.

**Parola hiçbir dosyaya, script'e veya ortam değişkenine yazılmaz.** Erişim anahtar tabanlı olacaktır.

**Durum: ÇÖZÜLDÜ (2026-08-06).**

Çözüm süreci:

- Parola doğrulaması `kkerem` ve `root` için reddedildi; ardışık denemeler fail2ban banını tetikledi (port 22 önce timeout, sonra "connection refused")
- Panel `104.143.0.74:2083` üzerinde HestiaCP olarak tespit edildi (varsayılan 8083 değil; `HESTIASID` çerezinden)
- Kullanıcı panelden ban'ı kaldırdı, **SSH Access** ayarını değiştirdi ve genel anahtarı **Users → kkerem → SSH Key** alanına yapıştırdı — parola hiçbir araç çağrısına girmedi
- Anahtar tabanlı kimlik doğrulama doğrulandı

**Sunucu SFTP-only'dir.** Kabuk erişimi yoktur: `ssh kkerem@... 'komut'` çağrısı `This service allows sftp connections only.` döner. Bu bir arıza değil, kasıtlı ve daha güvenli bir kısıtlamadır.

Yayın akışının ihtiyaç duyduğu üç işlemin tamamı SFTP ile karşılanır:

| İhtiyaç | SFTP karşılığı |
|---|---|
| Sürüm dizini oluştur | `-mkdir <yol>` (baştaki `-` hatayı yutar, dizin varsa iş durmaz) |
| Zip'i yükle | `put <yerel> <uzak>` |
| Boyut doğrula | `ls -l <yol>` → 5. alan (`awk '{print $5}'`) |

Doğrulanan mevcut durum: `/home/kkerem/web/lisans.kkerem.com/public_html/maya/` altında `1.2.6` … `1.3.7` sürüm dizinleri mevcut. Yerel `style.css:7` de `1.3.7` — örtüşüyor. Sıradaki sürüm `1.3.8`.

---

## Doğrulama

Yerel MAMP kurulumunda (`/mevzu2`) elle test edilir:

1. Bir resmi ilana öne çıkarılmış görsel eklenir, "Ana Manşette Göster" işaretlenir, kaydedilir → anasayfadaki ana manşet slider'ında görünmelidir.
2. Aynı ilan "Alt Manşette Göster" olarak da işaretlenir → her iki bölümde de görünmelidir.
3. Görseli olmayan bir ilanda manşet kutusu işaretlenir → metabox'ta uyarı görünmeli, kayıt yapılabilmeli, ilan manşette **çıkmamalıdır**.
4. Resmi ilan düzenleme ekranında "Yapay Zeka Manşetinde Göster" kutusu **görünmemelidir**.
5. Bir haberde manşet işaretlemesi yapılır → mevcut davranışın bozulmadığı doğrulanır (gerileme kontrolü).
6. İlan kaydedildikten hemen sonra anasayfa yenilenir → cache nedeniyle gecikme olmamalıdır.

Bölüm A için: bir örnek değişiklik yapılıp hook'un `CHANGELOG.md`'ye satır eklediği ve commit attığı gözlenir.

Bölüm C için — **ilk push'tan önce zorunlu kapı:**

1. `tests/test-sizinti.sh` (DESEN için betiğe bakınız) **hiçbir sonuç döndürmemelidir.**
2. `git check-ignore inc/config-keys.php` dosyanın yok sayıldığını doğrulamalıdır.
3. Namaz vakitleri, hava durumu ve nöbetçi eczane sayfaları yerel sitede açılıp verilerin hâlâ geldiği görülür (anahtarlar taşındıktan sonra çağrılar bozulmamalıdır).
4. Lisans doğrulaması yerelde denenir; `MEVZU_LICENSE_SHARED_SECRET` tanımlıyken ve tanımsızken aynı imzanın üretildiği kontrol edilir.

Bölüm D için: `/surum-cikar` test sürümüyle çalıştırılır; zip içeriği açılıp `docs/`, `graphify-out/`, `mevzu2-wp/` klasörlerinin **bulunmadığı** ve `inc/config-keys.php`'nin gerçek değerlerle **bulunduğu** doğrulanır. Yükleme adımı SSH erişimi kurulana kadar atlanır.

---

## Kapsam Dışı

- Resmi ilanlar için kategori/taksonomi eklenmesi
- Manşet kartlarının resmî ilanlara özel görsel tasarımı (rozet, etiket vb.)
- Arşiv sayfalarında (`archive.php`) resmî ilan gösterimi
- `render_ust_manset_gorsel()` ölü kodunun temizlenmesi — ayrı bir iş
- Otomatik sürüm numarası artırma (semver kararı kullanıcıda kalır)
- API çağrılarının `kkerem.com` üzerinden proxy'lenmesi — anahtarların dağıtılan zip'ten de çıkarılması (bkz. C2). `20-Referans/Güvenlik Borçları.md` notuna yazılır.
- Sunucu parolasının değiştirilmesi — parola bu oturumda düz metin paylaşıldığı için değiştirilmesi önerilir; bu **kullanıcı tarafında** yapılacak bir iştir.
- fail2ban banının kaldırılması ve panel SSH ayarı — kullanıcı tarafında (bkz. D3)
