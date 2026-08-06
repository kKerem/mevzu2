# Obsidian Vault Entegrasyonu ve Resmi İlan Manşetleri — Uygulama Planı

> **Agentic worker'lar için:** ZORUNLU ALT-SKILL: Bu planı görev görev uygulamak için `superpowers:subagent-driven-development` (önerilen) veya `superpowers:executing-plans` kullanın. Adımlar takip için checkbox (`- [ ]`) sözdizimi kullanır.

**Hedef:** mevzu2 WordPress temasına Obsidian tabanlı bilgi tabanı + otomatik changelog/sürüm akışı kazandırmak, sabit kodlanmış API anahtarlarını git dışına almak ve `resmi-ilanlar` post tipine manşet alanları eklemek.

**Mimari:** Git deposu tema köküne taşınır ve changelog'un tek kaynağı olur. Obsidian vault (`mevzu2-wp/`) tema ile birlikte versiyonlanır; bir Stop hook her iş bitiminde değişen dosyaları ilgili sistem notuyla eşleştirip CHANGELOG'a satır ekler. API anahtarları git dışı `inc/config-keys.php`'ye taşınır ve yalnızca yayın zip'ine enjekte edilir. Manşet özelliği mevcut `mevzu_manset_konumlari` post meta altyapısını `resmi-ilanlar`'a genişletir.

**Tech Stack:** PHP 8.x (MAMP), WordPress, Bash, git, `gh` CLI, `zip`, `rsync`/`scp`, Obsidian (Markdown + wiki-link).

## Global Constraints

- Yanıt ve tüm kullanıcıya görünen metinler **Türkçe**; teknik terimler ve kod tanımlayıcıları orijinal halinde kalır.
- Türkçe imlâ tam doğrulukla: `ğ ü ş ı ö ç İ` karakterleri ASCII karşılıklarıyla **değiştirilmez**.
- **Sunucu parolası hiçbir dosyaya, script'e, ortam değişkenine veya git geçmişine yazılmaz.** SSH erişimi yalnızca anahtar tabanlıdır.
- Uygulama sırası **A → C → B → D**'dir. Bölüm C, GitHub'a ilk push'tan **önce** bitmek zorundadır.
- GitHub deposu `kKerem/mevzu2` public'tir, varsayılan dal `main`. Uzaktaki 3 commit (`LICENSE`, `README.md`) korunur — **force-push yapılmaz**.
- Yayın hedefi: `/home/kkerem/web/lisans.kkerem.com/public_html/maya/<versiyon>/mevzu2.zip` (sunucu `104.143.0.74`, kullanıcı `kkerem`, port 22).
- **Sunucu SFTP-only'dir.** Anahtar tabanlı kimlik doğrulama çalışır, ancak kabuk erişimi yoktur — `ssh kkerem@... 'komut'` `This service allows sftp connections only.` döner. Uzak dizin oluşturma, yükleme ve boyut doğrulaması **yalnızca SFTP** ile yapılır (`-mkdir`, `put`, `ls -l`).
- Uzak sunucuda en son sürüm `1.3.7`, yerel `style.css:7` de `1.3.7` — ikisi örtüşüyor, sıradaki sürüm `1.3.8`.
- Vault yolu: `mevzu2-wp/` (tema kökünde). Spec: `docs/superpowers/specs/2026-08-06-obsidian-vault-ve-resmi-ilan-manseti-design.md`.
- CHANGELOG biçimi Keep a Changelog, Türkçe başlıklarla: `Eklendi`, `Değiştirildi`, `Düzeltildi`, `Kaldırıldı`.
- `resmi-ilanlar` için açılacak manşet alanları: `ust_manset`, `sicak_gundem`, `manset`, `yan_manset`, `alt_manset`. `yapay_zeka_manset` **açılmaz**.

## Test Stratejisi — Bu Projeye Özgü Sapma

Bu depoda PHPUnit, `tests/` dizini veya kurulu `vendor/` yok; `wp-cli` ve `node_modules` da kurulu değil. Klasik kırmızı-yeşil TDD döngüsü mevcut altyapıyla kurulamaz. Bunun yerine her görev şu üç katmanla doğrulanır:

1. **Sözdizimi kapısı** — `php -l` (MAMP PHP ile), her değiştirilen dosyada.
2. **Yalıtılmış davranış testi** — saf PHP mantığı için (`inc/keys.php` gibi) WordPress'siz çalışan, `assert` tabanlı bağımsız script. Bu script'ler `tests/` altında kalıcı tutulur.
3. **Tarayıcı doğrulaması** — WordPress'e bağımlı davranış için (metabox, sorgu, cache) MAMP'taki `http://localhost/mevzu2` üzerinde elle kontrol.

PHP yolu her görevde şu şekilde belirlenir:

```bash
PHP_BIN=$(ls -d /Applications/MAMP/bin/php/php*/bin/php | tail -1)
echo "Kullanılan PHP: $PHP_BIN"
```

## Dosya Yapısı

**Oluşturulacak:**

| Dosya | Sorumluluk |
|---|---|
| `.gitignore` | Tema kökü için yok sayma kuralları |
| `inc/keys.php` | `mevzu_key()` — anahtar okuma tek noktası (sabit → config-keys → varsayılan) |
| `inc/config-keys.sample.php` | Anahtar dosyasının boş şablonu (git'te) |
| `inc/config-keys.php` | Gerçek anahtarlar (git'te **değil**) |
| `tests/test-keys.php` | `mevzu_key()` için WordPress'siz assert testi |
| `mevzu2-wp/00-Genel/Tema Haritası.md` | Vault giriş noktası |
| `mevzu2-wp/10-Sistemler/*.md` | Sistem notları |
| `mevzu2-wp/20-Referans/*.md` | Meta anahtarları, hook'lar, güvenlik borçları |
| `mevzu2-wp/30-Degisiklikler/CHANGELOG.md` | Değişiklik günlüğü |
| `bin/vault-sync.sh` | Değişen dosya → sistem notu eşlemesi + CHANGELOG satırı |
| `bin/surum-cikar.sh` | Sürüm mühürleme, zip, yükleme, GitHub Release |
| `.claude/settings.json` | Stop hook tanımı |

**Değiştirilecek:**

| Dosya | Değişiklik |
|---|---|
| `functions.php:8` | `inc/keys.php` include edilir |
| `functions.php:4152` | Diyanet anahtarı → `mevzu_key()` |
| `functions.php:6228` | Hava durumu anahtarı → `mevzu_key()` |
| `functions.php:6602` | Transient temizlemeye `resmi-ilanlar` eklenir |
| `page-nobetci-eczaneler.php:84-87` | Eczane anahtarları → `mevzu_key()` |
| `inc/theme-settings/class-license.php:132` | Gömülü fallback → `mevzu_key()` |
| `inc/theme-settings/class-post-metabox.php:34` | Metabox `resmi-ilanlar`'a açılır |
| `inc/theme-settings/class-post-metabox.php:245` | YZ manşeti `resmi-ilanlar`'da gizlenir |
| `inc/theme-settings/class-post-metabox.php:646` | İzinli alan listesi post tipine göre daraltılır |
| `index.php:17,76,123,179,448` | 5 manşet sorgusu `resmi-ilanlar`'ı kapsar |
| `style.css` | `Version:` alanı (yalnızca sürüm çıkarken) |

---

## BÖLÜM A — Git Temeli ve Obsidian Vault

### Task 1: Git deposunu temaya taşı ve `.gitignore` kur

**Files:**
- Create: `.gitignore`
- Delete: `../.git` (üst depo — yalnızca doğrulamadan sonra)

**Interfaces:**
- Consumes: —
- Produces: `mevzu2/` kökünde `main` dalı üzerinde çalışan git deposu. Sonraki tüm görevler bu depoya commit atar.

- [ ] **Adım 1: Üst deponun gerçekten boş olduğunu doğrula**

```bash
cd /Applications/MAMP/htdocs/mevzu2/wp-content/themes
git log --oneline --all
git stash list
git tag
git remote -v
```

Beklenen: tek bir commit (`docs: Obsidian vault ve resmi ilan manşeti tasarım dokümanı`), stash yok, tag yok, remote yok.

**DUR:** Bu çıktıdan farklı bir şey görürsen — özellikle tanımadığın bir commit — silme yapma, kullanıcıya sor.

- [ ] **Adım 2: Spec dosyasını yedekle**

Üst depo silinince çalışma ağacındaki dosyalar durur, ama garanti olsun:

```bash
cp -R /Applications/MAMP/htdocs/mevzu2/wp-content/themes/mevzu2/docs \
      /tmp/mevzu2-docs-yedek
ls -R /tmp/mevzu2-docs-yedek
```

Beklenen: `specs/2026-08-06-obsidian-vault-ve-resmi-ilan-manseti-design.md` ve `plans/2026-08-06-obsidian-vault-ve-resmi-ilan-manseti.md` listelenir.

- [ ] **Adım 3: Üst depoyu kaldır**

```bash
rm -rf /Applications/MAMP/htdocs/mevzu2/wp-content/themes/.git
ls -la /Applications/MAMP/htdocs/mevzu2/wp-content/themes/.git 2>&1
```

Beklenen: `No such file or directory`.

`../.gitignore` dosyasına **dokunulmaz** — ilgisiz projelere ait.

- [ ] **Adım 4: `.gitignore` yaz**

`/Applications/MAMP/htdocs/mevzu2/wp-content/themes/mevzu2/.gitignore`:

```gitignore
# --- Sistem ---
.DS_Store
Untitled

# --- Bağımlılıklar ---
node_modules/
vendor/
package-lock.json
composer.lock

# --- Sırlar (asla commit edilmez) ---
inc/config-keys.php

# --- Üretilmiş / büyük dosyalar ---
graphify-out/cache/
graphify-out/graph.json
graphify-out/graph.html
*.psd
*.zip
*.sql
style.css.map

# --- Obsidian kişisel arayüz durumu ---
# Vault'un kendisi depoya DAHİLDİR; yalnızca kişisel pencere durumu hariç.
mevzu2-wp/.obsidian/workspace.json
mevzu2-wp/.obsidian/workspace-mobile.json

# --- Yayın çıktıları ---
dist/
```

- [ ] **Adım 5: Depoyu başlat**

```bash
cd /Applications/MAMP/htdocs/mevzu2/wp-content/themes/mevzu2
git init -b main
git add .gitignore
git status --short | head -20
```

Beklenen: `.gitignore` staged; `node_modules`, `*.zip`, `screenshot.psd` listede **görünmemeli**.

- [ ] **Adım 6: Yok sayma kurallarının çalıştığını doğrula**

```bash
git check-ignore -v inc/config-keys.php node_modules graphify-out/graph.json screenshot.psd
git status --short | wc -l
```

Beklenen: dört yol için de eşleşen kural yazdırılır. Dosya sayısı 78 MB'lık ham dizinden çok daha az olmalı (~500 civarı, 10.000+ değil).

- [ ] **Adım 7: Başlangıç commit'i**

```bash
git add -A
git commit -m "chore: mevzu2 temasının ilk sürümü

Depo tema köküne taşındı. Önceki kök wp-content/themes/ idi ve
ilgisiz projeleri de kapsıyordu.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git log --oneline
git rev-parse --show-toplevel
```

Beklenen: tek commit; toplevel `.../themes/mevzu2`.

---

### Task 2: Vault iskeleti ve CHANGELOG

**Files:**
- Create: `mevzu2-wp/00-Genel/Tema Haritası.md`, `mevzu2-wp/30-Degisiklikler/CHANGELOG.md`
- Modify: `mevzu2-wp/Welcome.md` (silinir)

**Interfaces:**
- Consumes: Task 1'in git deposu
- Produces: `mevzu2-wp/30-Degisiklikler/CHANGELOG.md` — Task 4'teki hook ve Task 13'teki sürüm script'i bu dosyanın `## [Yayınlanmamış]` başlığını arar. Başlık metni **birebir** budur.

- [ ] **Adım 1: Klasörleri oluştur**

```bash
cd /Applications/MAMP/htdocs/mevzu2/wp-content/themes/mevzu2
mkdir -p "mevzu2-wp/00-Genel" "mevzu2-wp/10-Sistemler" "mevzu2-wp/20-Referans" "mevzu2-wp/30-Degisiklikler"
rm -f mevzu2-wp/Welcome.md
```

- [ ] **Adım 2: CHANGELOG'u oluştur**

`mevzu2-wp/30-Degisiklikler/CHANGELOG.md`:

```markdown
# Değişiklik Günlüğü

Bu dosyanın biçimi [Keep a Changelog](https://keepachangelog.com/tr/1.1.0/)
sözleşmesine dayanır. Sürüm numaraları [Semantic Versioning](https://semver.org/lang/tr/)
kurallarını izler.

Kategoriler: **Eklendi**, **Değiştirildi**, **Düzeltildi**, **Kaldırıldı**.

## [Yayınlanmamış]

### Eklendi

- Obsidian tabanlı tema bilgi tabanı ve otomatik changelog akışı kuruldu.
```

- [ ] **Adım 3: Vault giriş notunu oluştur**

`mevzu2-wp/00-Genel/Tema Haritası.md`:

```markdown
---
tags: [giris]
---

# Tema Haritası

mevzu2, haber sitesi olarak çalışan bir WordPress temasıdır. Bu vault temanın
nasıl çalıştığını sistem sistem anlatır.

## Sistemler

- [[Manşet Sistemi]] — anasayfadaki beş manşet bölgesi ve seçim mekanizması
- [[Resmi İlanlar]] — `resmi-ilanlar` post tipi, şablonları ve ilan numarası
- [[TTS ve Seslendirme]] — haber seslendirme, "Günün Özeti"
- [[Tema Ayarları]] — yönetim paneli seçenekleri
- [[Lisans Sistemi]] — lisans doğrulama ve HMAC imzalama

## Referans

- [[Meta Anahtarları]] — post meta ve option anahtarlarının tam listesi
- [[Hooklar]] — temanın bağlandığı action ve filter'lar
- [[Güvenlik Borçları]] — bilinen, henüz kapatılmamış güvenlik konuları

## Değişiklikler

Sürüm geçmişi için `30-Degisiklikler/CHANGELOG.md` dosyasına bakın.
Her iş bitiminde otomatik güncellenir.
```

- [ ] **Adım 4: Commit**

```bash
git add mevzu2-wp/
git commit -m "docs: Obsidian vault iskeleti ve CHANGELOG

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 3: Sistem notlarını yaz

**Files:**
- Create: `mevzu2-wp/10-Sistemler/Manşet Sistemi.md`, `Resmi İlanlar.md`, `TTS ve Seslendirme.md`, `Tema Ayarları.md`, `Lisans Sistemi.md`
- Create: `mevzu2-wp/20-Referans/Meta Anahtarları.md`, `Hooklar.md`, `Güvenlik Borçları.md`

**Interfaces:**
- Consumes: `graphify-out/GRAPH_REPORT.md` ve `manifest.json` (hammadde), Task 2'nin klasör yapısı
- Produces: `[[Manşet Sistemi]]` ve `[[Resmi İlanlar]]` notları — Task 4'teki `bin/vault-sync.sh` bu dosya adlarını eşleme tablosunda kullanır.

- [ ] **Adım 1: Graphify raporunu oku**

```bash
head -120 graphify-out/GRAPH_REPORT.md
```

Sistem sınırlarını ve dosya gruplarını buradan çıkar. Kodu sıfırdan taramaya gerek yok.

- [ ] **Adım 2: `Manşet Sistemi.md` yaz**

`mevzu2-wp/10-Sistemler/Manşet Sistemi.md`:

```markdown
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
- `inc/theme-settings/class-post-metabox.php:637` — kaydetme
- `index.php` — beş sorgu (yukarıdaki tablo)
- `functions.php:6596` — transient temizleme

## Meta anahtarları

- `mevzu_manset_konumlari` (dizi) — işaretli bölge anahtarları
- `ust_manset_gorseli_id`, `ust_manset_gorseli_url` — Üst Manşet görseli

## Önbellek

Manşet sorguları süresiz transient ile saklanır (`set_transient(..., 0)`).
`functions.php:6596`'daki `clear_custom_post_transients()` kayıt sırasında
temizler. **Bu fonksiyon kapsamında olmayan bir post tipi manşete
eklenirse anasayfa güncellenmez.**

## Bağlantılı

[[Resmi İlanlar]] · [[Tema Ayarları]] · [[TTS ve Seslendirme]]
```

- [ ] **Adım 3: `Resmi İlanlar.md` yaz**

`mevzu2-wp/10-Sistemler/Resmi İlanlar.md`:

```markdown
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

- `init.php:90` — yeni ilan taslağının yayın tarihi otomatik **yarın 00:00** yapılır
- `init.php:103` — kendi şablonlarını yükler (`templates/single-*`, `templates/archive-*`)
- `init.php:133` — "İlan Numarası" metabox'ı (`ilan_numarasi` meta)
- `init.php:167` — breadcrumb'da üst öğeler gizlenir

## Manşet desteği

Manşet alanları (`Sayfa Ayarları`) bu post tipinde de açıktır.
Ayrıntı: [[Manşet Sistemi]].
`yapay_zeka_manset` bu tipte **kapalıdır** — ilan metinleri seslendirmeye uygun değildir.

## Bağlantılı

[[Manşet Sistemi]] · [[Tema Ayarları]]
```

- [ ] **Adım 4: `Güvenlik Borçları.md` yaz**

`mevzu2-wp/20-Referans/Güvenlik Borçları.md`:

```markdown
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
```

- [ ] **Adım 5: Kalan notları aynı iskeletle yaz**

`TTS ve Seslendirme.md`, `Tema Ayarları.md`, `Lisans Sistemi.md`,
`Meta Anahtarları.md`, `Hooklar.md` — her biri şu altı başlığı taşır:
**Ne işe yarar → İlgili dosyalar (satır referanslı) → Meta anahtarları →
Hook'lar → Bilinen tuzaklar → Bağlantılı**.

Kaynaklar:
- TTS: `inc/tts/` (özellikle `class-tts-helpers.php:34`, `class-admin.php`)
- Tema Ayarları: `inc/theme-settings/`, `get_opt_g()` (`functions.php:6414`)
- Lisans: `inc/theme-settings/class-license.php`

- [ ] **Adım 6: Wiki-link bütünlüğünü doğrula**

```bash
cd mevzu2-wp
grep -rho "\[\[[^]]*\]\]" . | tr -d '[]' | sort -u > /tmp/linkler.txt
ls */*.md | sed 's|.*/||; s|\.md$||' | sort -u > /tmp/notlar.txt
comm -23 /tmp/linkler.txt /tmp/notlar.txt
```

Beklenen: çıktı boş. Boş değilse listelenen her ad için ya not oluştur ya da linki düzelt.

- [ ] **Adım 7: Commit**

```bash
cd /Applications/MAMP/htdocs/mevzu2/wp-content/themes/mevzu2
git add mevzu2-wp/
git commit -m "docs: sistem ve referans notları

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 4: Otomatik senkronizasyon hook'u

**Files:**
- Create: `bin/vault-sync.sh`
- Create/Modify: `.claude/settings.json`

**Interfaces:**
- Consumes: Task 2'nin `## [Yayınlanmamış]` başlıklı CHANGELOG'u, Task 3'ün sistem notu adları
- Produces: `bin/vault-sync.sh` — Task 13'teki `bin/surum-cikar.sh` bu script'in ürettiği CHANGELOG yapısına güvenir.

- [ ] **Adım 1: Eşleme mantığı için testi yaz**

`tests/test-vault-sync.sh`:

```bash
#!/usr/bin/env bash
# bin/vault-sync.sh içindeki dosya→sistem eşlemesini doğrular.
set -uo pipefail
cd "$(dirname "$0")/.."
source bin/vault-sync.sh --sadece-fonksiyonlar

basarisiz=0
kontrol() {
  local girdi="$1" beklenen="$2" sonuc
  sonuc="$(sistem_bul "$girdi")"
  if [ "$sonuc" != "$beklenen" ]; then
    echo "BASARISIZ: $girdi -> '$sonuc' (beklenen '$beklenen')"
    basarisiz=1
  fi
}

kontrol "index.php"                                  "Manşet Sistemi"
kontrol "inc/theme-settings/class-post-metabox.php"  "Manşet Sistemi"
kontrol "inc/resmi-ilanlar/init.php"                 "Resmi İlanlar"
kontrol "inc/tts/class-admin.php"                    "TTS ve Seslendirme"
kontrol "inc/theme-settings/class-license.php"       "Lisans Sistemi"
kontrol "css/ozel.css"                               ""

[ "$basarisiz" -eq 0 ] && echo "TUM TESTLER GECTI"
exit "$basarisiz"
```

- [ ] **Adım 2: Testi çalıştır, başarısız olduğunu gör**

```bash
chmod +x tests/test-vault-sync.sh
./tests/test-vault-sync.sh
```

Beklenen: `bin/vault-sync.sh: No such file or directory` — script henüz yok.

- [ ] **Adım 3: `bin/vault-sync.sh` yaz**

```bash
#!/usr/bin/env bash
# Değişen dosyaları sistem notlarıyla eşler ve CHANGELOG'a satır ekler.
set -uo pipefail

VAULT="mevzu2-wp"
CHANGELOG="$VAULT/30-Degisiklikler/CHANGELOG.md"

# Dosya yolu -> sistem notu adı. İlk eşleşen kazanır.
sistem_bul() {
  case "$1" in
    index.php|archive.php)                  echo "Manşet Sistemi" ;;
    inc/theme-settings/class-license.php)   echo "Lisans Sistemi" ;;
    inc/theme-settings/class-post-metabox.php) echo "Manşet Sistemi" ;;
    inc/theme-settings/*)                   echo "Tema Ayarları" ;;
    inc/resmi-ilanlar/*)                    echo "Resmi İlanlar" ;;
    inc/tts/*)                              echo "TTS ve Seslendirme" ;;
    *)                                      echo "" ;;
  esac
}

[ "${1:-}" = "--sadece-fonksiyonlar" ] && return 0 2>/dev/null || true

degisenler="$(git diff --name-only HEAD; git diff --cached --name-only)"
degisenler="$(echo "$degisenler" | sort -u | grep -v '^mevzu2-wp/' || true)"

if [ -z "$degisenler" ]; then
  echo "vault-sync: değişiklik yok, atlanıyor."
  exit 0
fi

etkilenen="$(while read -r f; do
  [ -n "$f" ] && sistem_bul "$f"
done <<< "$degisenler" | grep -v '^$' | sort -u)"

echo "vault-sync: değişen dosyalar:"
echo "$degisenler" | sed 's/^/  - /'
[ -n "$etkilenen" ] && { echo "vault-sync: etkilenen sistemler:"; echo "$etkilenen" | sed 's/^/  - /'; }

if ! grep -q '^## \[Yayınlanmamış\]' "$CHANGELOG"; then
  echo "vault-sync: HATA — CHANGELOG'da '## [Yayınlanmamış]' başlığı yok." >&2
  exit 1
fi

echo "vault-sync: CHANGELOG hazır. Değişikliği açıklayan satırı ekleyin."
```

- [ ] **Adım 4: Testi çalıştır, geçtiğini gör**

```bash
chmod +x bin/vault-sync.sh
./tests/test-vault-sync.sh
```

Beklenen: `TUM TESTLER GECTI`.

- [ ] **Adım 5: Hook'u `.claude/settings.json`'a bağla**

Dosya varsa `hooks` anahtarı **birleştirilir**, üzerine yazılmaz.

```json
{
  "hooks": {
    "Stop": [
      {
        "matcher": "",
        "hooks": [
          {
            "type": "command",
            "command": "cd \"$CLAUDE_PROJECT_DIR\" && ./bin/vault-sync.sh"
          }
        ]
      }
    ]
  }
}
```

- [ ] **Adım 6: Hook'un çalıştığını doğrula**

```bash
touch index.php && ./bin/vault-sync.sh; git checkout index.php 2>/dev/null || true
```

Beklenen: `index.php` değişen olarak listelenir, `Manşet Sistemi` etkilenen sistem olarak yazdırılır.

- [ ] **Adım 7: Commit**

```bash
git add bin/vault-sync.sh tests/test-vault-sync.sh .claude/settings.json
git commit -m "feat: vault senkronizasyon hook'u

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## BÖLÜM C — Sırların Git Dışına Alınması

> **Bu bölüm GitHub'a ilk push'tan (Task 12) önce bitmek zorundadır.**

### Task 5: `mevzu_key()` altyapısı

**Files:**
- Create: `inc/keys.php`, `inc/config-keys.sample.php`, `inc/config-keys.php`, `tests/test-keys.php`
- Modify: `functions.php:8`

**Interfaces:**
- Consumes: Task 1'in `.gitignore`'undaki `inc/config-keys.php` kuralı
- Produces: `mevzu_key( string $ad, mixed $varsayilan = '' ): mixed` — Task 6 bu fonksiyonu çağırır. Geçerli `$ad` değerleri: `license_shared_secret`, `weather_api_key`, `diyanet_api_key`, `eczane_api_keys`.

- [ ] **Adım 1: Testi yaz**

`tests/test-keys.php`:

```php
<?php
/**
 * mevzu_key() için WordPress'siz test.
 * Çalıştırma: php tests/test-keys.php
 */
define( 'ABSPATH', __DIR__ . '/../' );

$gecici = sys_get_temp_dir() . '/mevzu-test-keys-' . getmypid();
mkdir( $gecici );
copy( __DIR__ . '/../inc/keys.php', $gecici . '/keys.php' );
file_put_contents( $gecici . '/config-keys.php', <<<'PHP'
<?php
return [
    'weather_api_key' => 'dosyadan-gelen',
    'eczane_api_keys' => [ 'a', 'b' ],
];
PHP
);

define( 'MEVZU_LICENSE_SHARED_SECRET', 'sabitten-gelen' );
require $gecici . '/keys.php';

$basarisiz = 0;
function kontrol( $ad, $beklenen, $sonuc ) {
    global $basarisiz;
    if ( $sonuc !== $beklenen ) {
        printf( "BASARISIZ: %s -> %s (beklenen %s)\n", $ad,
            var_export( $sonuc, true ), var_export( $beklenen, true ) );
        $basarisiz = 1;
    }
}

// 1. Sabit tanımlıysa sabit kazanır.
kontrol( 'sabit onceligi', 'sabitten-gelen', mevzu_key( 'license_shared_secret' ) );
// 2. Sabit yoksa config-keys.php'den okunur.
kontrol( 'dosyadan okuma', 'dosyadan-gelen', mevzu_key( 'weather_api_key' ) );
// 3. Dizi değerler bozulmadan döner.
kontrol( 'dizi degeri', [ 'a', 'b' ], mevzu_key( 'eczane_api_keys' ) );
// 4. Bilinmeyen anahtar varsayılanı döner.
kontrol( 'varsayilan', 'yok', mevzu_key( 'olmayan_anahtar', 'yok' ) );
// 5. Varsayılan verilmezse boş dize döner.
kontrol( 'bos varsayilan', '', mevzu_key( 'olmayan_anahtar_2' ) );

unlink( $gecici . '/keys.php' );
unlink( $gecici . '/config-keys.php' );
rmdir( $gecici );

echo $basarisiz ? "TESTLER BASARISIZ\n" : "TUM TESTLER GECTI\n";
exit( $basarisiz );
```

- [ ] **Adım 2: Testi çalıştır, başarısız olduğunu gör**

```bash
PHP_BIN=$(ls -d /Applications/MAMP/bin/php/php*/bin/php | tail -1)
$PHP_BIN tests/test-keys.php
```

Beklenen: `Failed to open stream: ... inc/keys.php` — dosya henüz yok.

- [ ] **Adım 3: `inc/keys.php` yaz**

```php
<?php
/**
 * Harici servis anahtarlarının tek okuma noktası.
 *
 * Öncelik: wp-config.php sabiti → inc/config-keys.php → varsayılan.
 * Gerçek değerler git'e DAHİL DEĞİLDİR (bkz. .gitignore).
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'mevzu_key' ) ) {
    /**
     * @param string $ad         license_shared_secret|weather_api_key|diyanet_api_key|eczane_api_keys
     * @param mixed  $varsayilan Anahtar hiçbir kaynakta yoksa dönecek değer.
     * @return mixed
     */
    function mevzu_key( string $ad, $varsayilan = '' ) {
        static $dosya_degerleri = null;

        $sabitler = [
            'license_shared_secret' => 'MEVZU_LICENSE_SHARED_SECRET',
            'weather_api_key'       => 'MEVZU_WEATHER_API_KEY',
            'diyanet_api_key'       => 'MEVZU_DIYANET_API_KEY',
            'eczane_api_keys'       => 'MEVZU_ECZANE_API_KEYS',
        ];

        if ( isset( $sabitler[ $ad ] ) && defined( $sabitler[ $ad ] ) ) {
            $deger = constant( $sabitler[ $ad ] );
            if ( '' !== $deger && [] !== $deger && null !== $deger ) {
                return $deger;
            }
        }

        if ( null === $dosya_degerleri ) {
            $yol = __DIR__ . '/config-keys.php';
            $dosya_degerleri = file_exists( $yol ) ? (array) require $yol : [];
        }

        return array_key_exists( $ad, $dosya_degerleri )
            ? $dosya_degerleri[ $ad ]
            : $varsayilan;
    }
}
```

- [ ] **Adım 4: Testi çalıştır, geçtiğini gör**

```bash
$PHP_BIN tests/test-keys.php
```

Beklenen: `TUM TESTLER GECTI`, çıkış kodu 0.

- [ ] **Adım 5: `config-keys.sample.php` yaz**

```php
<?php
/**
 * Harici servis anahtarları — ŞABLON.
 *
 * Bu dosyayı `config-keys.php` olarak kopyalayıp değerleri doldurun.
 * `config-keys.php` git'e DAHİL DEĞİLDİR ve yalnızca yayın zip'ine enjekte edilir.
 *
 * Alternatif: değerleri wp-config.php içinde sabit olarak tanımlayabilirsiniz
 * (MEVZU_LICENSE_SHARED_SECRET, MEVZU_WEATHER_API_KEY, MEVZU_DIYANET_API_KEY,
 * MEVZU_ECZANE_API_KEYS). Sabit tanımlıysa o kazanır.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    'license_shared_secret' => '',
    'weather_api_key'       => '',
    'diyanet_api_key'       => '',
    'eczane_api_keys'       => [],
];
```

- [ ] **Adım 6: Gerçek `config-keys.php`'yi oluştur**

Şablonu kopyala, değerleri mevcut koddan taşı:

| Anahtar | Kaynak |
|---|---|
| `license_shared_secret` | `inc/theme-settings/class-license.php:132` |
| `weather_api_key` | `functions.php:6228` |
| `diyanet_api_key` | `functions.php:4152` |
| `eczane_api_keys` | `page-nobetci-eczaneler.php:84-87` (dizi, 4 eleman, sırayı koru) |

```bash
git check-ignore -v inc/config-keys.php
git status --short inc/
```

Beklenen: `.gitignore` kuralı eşleşir; `git status` bu dosyayı **listelemez**.

- [ ] **Adım 7: `functions.php`'ye include ekle**

`functions.php:8`'deki `include('inc/ajax.php');` satırının **üstüne**:

```php
include('inc/keys.php');
```

`inc/theme-settings/init.php` satır 664'te yüklendiği için `class-license.php` bu noktadan sonra gelir — sıralama doğrudur.

- [ ] **Adım 8: Sözdizimi ve commit**

```bash
$PHP_BIN -l functions.php && $PHP_BIN -l inc/keys.php && $PHP_BIN -l inc/config-keys.sample.php
git add inc/keys.php inc/config-keys.sample.php tests/test-keys.php functions.php
git commit -m "feat: anahtar okuma altyapısı (mevzu_key)

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 6: Anahtarları taşı ve sızıntı kapısını kur

**Files:**
- Modify: `functions.php:4152`, `functions.php:6228`, `page-nobetci-eczaneler.php:84-87`, `inc/theme-settings/class-license.php:128-133`
- Create: `tests/test-sizinti.sh`

**Interfaces:**
- Consumes: Task 5'in `mevzu_key()` fonksiyonu
- Produces: `tests/test-sizinti.sh` — Task 12 (ilk push) bu script sıfır dönmeden çalıştırılmaz.

- [ ] **Adım 1: Sızıntı testini yaz**

`tests/test-sizinti.sh`:

```bash
#!/usr/bin/env bash
# Bilinen sırların takip edilen dosyalarda kalmadığını doğrular.
set -uo pipefail
cd "$(dirname "$0")/.."

DESEN='<gerçek çalışan betik için bkz. tests/test-sizinti.sh — sır sızıntısını önlemek amacıyla bu dokümanda tekrarlanmıyor>'

echo "== Takip edilen dosyalarda sır taraması =="
if git grep -nE "$DESEN" -- . ':!tests/test-sizinti.sh'; then
  echo "BASARISIZ: yukarıdaki konumlarda sır bulundu."
  exit 1
fi

echo "== config-keys.php yok sayılıyor mu =="
if ! git check-ignore -q inc/config-keys.php; then
  echo "BASARISIZ: inc/config-keys.php yok sayılmıyor."
  exit 1
fi

echo "== config-keys.php takip ediliyor mu =="
if git ls-files --error-unmatch inc/config-keys.php >/dev/null 2>&1; then
  echo "BASARISIZ: inc/config-keys.php git tarafından takip ediliyor."
  exit 1
fi

echo "TUM TESTLER GECTI"
```

- [ ] **Adım 2: Testi çalıştır, başarısız olduğunu gör**

```bash
chmod +x tests/test-sizinti.sh
./tests/test-sizinti.sh
```

Beklenen: `BASARISIZ` — 7 eşleşme listelenir (`functions.php` 2, `page-nobetci-eczaneler.php` 4, `class-license.php` 1).

- [ ] **Adım 3: `class-license.php` fallback'ini değiştir**

`inc/theme-settings/class-license.php:128-133`:

```php
    public static function get_shared_secret(): string {
        if (defined('MEVZU_LICENSE_SHARED_SECRET') && MEVZU_LICENSE_SHARED_SECRET !== '') {
            return (string) MEVZU_LICENSE_SHARED_SECRET;
        }
        return (string) mevzu_key('license_shared_secret');
    }
```

- [ ] **Adım 4: `functions.php`'deki iki anahtarı değiştir**

Satır 4152:

```php
    $apiKey    = mevzu_key('diyanet_api_key');
```

Satır 6228:

```php
    $api_key = mevzu_key('weather_api_key');
```

- [ ] **Adım 5: `page-nobetci-eczaneler.php`'deki diziyi değiştir**

Satır 83-88 arasındaki `$api_keys = [ ... ];` bloğu:

```php
                $api_keys = (array) mevzu_key('eczane_api_keys', []);
                if (empty($api_keys)) {
                    return false;
                }
```

`return false;` eklenmesinin nedeni: alttaki `$api_keys[$current_key_index]` boş dizide tanımsız indeks hatası verirdi.

- [ ] **Adım 6: Testi çalıştır, geçtiğini gör**

```bash
./tests/test-sizinti.sh
```

Beklenen: `TUM TESTLER GECTI`.

- [ ] **Adım 7: Sözdizimi kontrolü**

```bash
PHP_BIN=$(ls -d /Applications/MAMP/bin/php/php*/bin/php | tail -1)
for f in functions.php page-nobetci-eczaneler.php inc/theme-settings/class-license.php; do
  $PHP_BIN -l "$f" || echo "SOZDIZIMI HATASI: $f"
done
```

Beklenen: her biri için `No syntax errors detected`.

- [ ] **Adım 8: Tarayıcı doğrulaması**

MAMP'ta sırayla aç, verilerin hâlâ geldiğini gör:

1. `http://localhost/mevzu2/namaz-vakitleri/` — vakit tablosu dolu
2. `http://localhost/mevzu2/hava-durumu/` — sıcaklık değerleri geliyor
3. `http://localhost/mevzu2/nobetci-eczaneler/` — eczane listesi dolu

**Not:** bu sayfalar transient ile cache'lenir. Taze veri için önce ilgili transient'ları temizle veya cache süresinin dolmasını bekle.

- [ ] **Adım 9: Commit**

```bash
git add functions.php page-nobetci-eczaneler.php inc/theme-settings/class-license.php tests/test-sizinti.sh
git commit -m "refactor: API anahtarlarını git dışına taşı

Lisans HMAC anahtarı, hava durumu, diyanet ve 4 eczane anahtarı artık
inc/config-keys.php içinden mevzu_key() ile okunuyor. Depo public
olacağı için bu dosya .gitignore'da.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## BÖLÜM B — Resmi İlanlara Manşet Alanları

### Task 7: Metabox'ı `resmi-ilanlar` için aç

**Files:**
- Modify: `inc/theme-settings/class-post-metabox.php:30-37`, `:245`, `:225-240`

**Interfaces:**
- Consumes: —
- Produces: `resmi-ilanlar` düzenleme ekranında `mevzu-sayfa-ayarlari` metabox'ı. Task 8 aynı dosyanın kaydetme tarafını daraltır.

- [ ] **Adım 1: Metabox kaydını genişlet**

`inc/theme-settings/class-post-metabox.php:30-37`, `add_meta_box` çağrısının dördüncü argümanı:

```php
        // Yazı ve Resmi İlan — Sayfa Ayarları (Manşet konumları)
        add_meta_box(
            'mevzu-sayfa-ayarlari',
            __( 'Sayfa Ayarları', 'mevzu2' ),
            [ $this, 'render_sayfa_ayarlari' ],
            [ 'post', 'resmi-ilanlar' ],
            'side',
            'high'
        );
```

Aynı dosyadaki `mevzu-native-video` ve `mevzu-sosyal-gonderi` metabox'ları `'post'` olarak **kalır**.

- [ ] **Adım 2: Yapay Zeka Manşeti bloğunu resmi ilanlarda gizle**

Satır 245'teki koşula post tipi şartı eklenir:

```php
            if ( 'resmi-ilanlar' !== $post->post_type && $tts_target && ! $in_tts_cat && function_exists( 'mevzu_yz_module_active' ) && mevzu_yz_module_active() ) :
```

- [ ] **Adım 3: Görsel uyarısını ekle**

Satır 240'taki `<?php endforeach; ?>`'in hemen **altına**:

```php
            <?php
            // Görsel şartı olan konumlar: sorguları _thumbnail_id EXISTS koşuyor.
            $gorsel_gerektiren = [ 'manset', 'sicak_gundem', 'alt_manset' ];
            $thumb_var         = (int) get_post_thumbnail_id( $post->ID ) > 0;
            ?>
            <p id="mevzu-manset-gorsel-uyari"
               class="mb-0 mt-1 text-danger"
               data-konumlar="<?php echo esc_attr( wp_json_encode( $gorsel_gerektiren ) ); ?>"
               style="<?php echo $thumb_var ? 'display:none' : ''; ?>">
                <?php esc_html_e( 'Manşette görünmesi için öne çıkarılmış görsel eklemelisiniz.', 'mevzu2' ); ?>
            </p>
            <script>
            jQuery(function($) {
                var $uyari   = $('#mevzu-manset-gorsel-uyari');
                var konumlar = $uyari.data('konumlar') || [];

                function thumbVar() {
                    var $alan = $('#_thumbnail_id');
                    if ($alan.length) {
                        var v = parseInt($alan.val(), 10);
                        if (v === -1) { return false; }
                        if (v > 0)    { return true; }
                    }
                    return $('#postimagediv .inside').find('img').length > 0;
                }

                function isaretliMi() {
                    return konumlar.some(function(k) {
                        return $('#manset_konum_' + k).is(':checked');
                    });
                }

                function senkronla() {
                    $uyari.toggle(isaretliMi() && !thumbVar());
                }

                $(document).on('change', 'input[name="mevzu_manset_konumlari[]"], #_thumbnail_id', senkronla);

                var $kutu = $('#postimagediv');
                if ($kutu.length && window.MutationObserver) {
                    new MutationObserver(senkronla).observe($kutu[0], { childList: true, subtree: true });
                }

                senkronla();
            });
            </script>
```

Uyarı yalnızca bilgilendiricidir — kaydetmeyi **engellemez**.

- [ ] **Adım 4: Sözdizimi kontrolü**

```bash
PHP_BIN=$(ls -d /Applications/MAMP/bin/php/php*/bin/php | tail -1)
$PHP_BIN -l inc/theme-settings/class-post-metabox.php
```

Beklenen: `No syntax errors detected`.

- [ ] **Adım 5: Tarayıcı doğrulaması**

1. `http://localhost/mevzu2/wp-admin/post-new.php?post_type=resmi-ilanlar`
2. Sağ sütunda **Sayfa Ayarları** kutusu görünmeli, beş manşet seçeneği listelenmeli
3. **"Yapay Zeka Manşetinde Göster" görünmemeli**
4. Görsel yokken "Ana Manşette Göster" işaretlenince kırmızı uyarı çıkmalı
5. Görsel eklenince uyarı kaybolmalı

- [ ] **Adım 6: Commit**

```bash
git add inc/theme-settings/class-post-metabox.php
git commit -m "feat: Sayfa Ayarları metabox'ını resmi ilanlara aç

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 8: Kaydetme izin listesini post tipine göre daralt

**Files:**
- Modify: `inc/theme-settings/class-post-metabox.php:646`

**Interfaces:**
- Consumes: Task 7'nin metabox'ı
- Produces: `resmi-ilanlar` için `yapay_zeka_manset` içermeyen `mevzu_manset_konumlari` meta değeri.

- [ ] **Adım 1: İzin listesini koşullu yap**

Satır 646'daki `$allowed` tanımı:

```php
            $allowed = [ 'ust_manset', 'manset', 'sicak_gundem', 'alt_manset', 'yan_manset', 'yapay_zeka_manset' ];
            if ( 'resmi-ilanlar' === $post->post_type ) {
                // İlan metinleri seslendirmeye uygun değil; alan arayüzde de gizli.
                $allowed = array_values( array_diff( $allowed, [ 'yapay_zeka_manset' ] ) );
            }
```

`$post` parametresi zaten `save_post_meta( $post_id, $post )` imzasında mevcuttur (satır 637).

- [ ] **Adım 2: Sözdizimi kontrolü**

```bash
$PHP_BIN -l inc/theme-settings/class-post-metabox.php
```

Beklenen: `No syntax errors detected`.

- [ ] **Adım 3: Doğrulama**

Tarayıcının geliştirici konsolunda resmi ilan düzenleme ekranında:

```js
jQuery('<input type="checkbox" name="mevzu_manset_konumlari[]" value="yapay_zeka_manset" checked>')
  .appendTo('#mevzu-sayfa-ayarlari .mevzu-metabox');
```

Sonra kaydet. Veritabanında değerin yazılmadığını doğrula:

```bash
# MAMP MySQL üzerinden, <ID> ilanın post ID'si
/Applications/MAMP/Library/bin/mysql -u root -proot mevzu2 -e \
  "SELECT meta_value FROM wp_postmeta WHERE post_id=<ID> AND meta_key='mevzu_manset_konumlari';"
```

Beklenen: çıktıda `yapay_zeka_manset` **geçmemeli**.

- [ ] **Adım 4: Commit**

```bash
git add inc/theme-settings/class-post-metabox.php
git commit -m "fix: resmi ilanlarda yapay_zeka_manset alanını kaydetmeyi engelle

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 9: Anasayfa sorgularını `resmi-ilanlar`'a aç

**Files:**
- Modify: `index.php:17`, `:76`, `:123`, `:179`, `:448`

**Interfaces:**
- Consumes: Task 7-8'in yazdığı `mevzu_manset_konumlari` meta değeri
- Produces: Anasayfada beş manşet bölgesinde resmi ilan gösterimi.

- [ ] **Adım 1: Hedef satırları teyit et**

```bash
grep -nE "'post_type'" index.php | head -14
grep -nE "mevzu_manset_konumlari" index.php
```

Beklenen eşleşme çiftleri:

| `post_type` satırı | Doğrulayan `meta_query` satırı | Bölge |
|---|---|---|
| 17 | 22 → `'ust_manset'` | Üst Manşet |
| 76 | 83 → `'sicak_gundem'` | Sıcak Gündem |
| 123 | 130 → `'"manset"'` | Ana Manşet |
| 179 | 184 → `'yan_manset'` | Yan Manşet |
| 448 | 455 → `'alt_manset'` | Alt Manşet |

**DUR:** Satır numaraları tutmuyorsa (önceki görevlerde dosya değiştiyse) `meta_query` değerinden yukarı doğru en yakın `post_type` satırını bul. **Toplu değiştirme (`sed -i` ile hepsini birden) yapma** — dosyada manşetle ilgisiz 9 `post_type` satırı daha var (`:265`, `:412`, `:543`, `:600`, `:671`, `:718`, `:797`, `:831`, `:879`).

- [ ] **Adım 2: Beş satırı tek tek değiştir**

Her birinde girinti korunarak:

```php
'post_type'      => [ 'post', 'resmi-ilanlar' ],
```

- [ ] **Adım 3: Tam olarak 5 değişiklik olduğunu doğrula**

```bash
git diff --numstat index.php
grep -c "'post_type'      => \[ 'post', 'resmi-ilanlar' \]," index.php
```

Beklenen: `5`. Farklıysa fazladan/eksik değişiklik var, `git diff index.php` ile incele.

- [ ] **Adım 4: Sözdizimi kontrolü**

```bash
$PHP_BIN -l index.php
```

Beklenen: `No syntax errors detected`.

- [ ] **Adım 5: Commit**

```bash
git add index.php
git commit -m "feat: anasayfa manşet sorgularına resmi ilanları dahil et

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 10: Transient temizlemeyi `resmi-ilanlar`'a genişlet

**Files:**
- Modify: `functions.php:6601-6603`

**Interfaces:**
- Consumes: Task 9'un sorguları
- Produces: Resmi ilan kaydında manşet transient'larının temizlenmesi. **Bu görev olmadan Task 9 kullanıcıya görünmez.**

- [ ] **Adım 1: Erken çıkış koşulunu genişlet**

`functions.php:6601-6603`:

```php
    if ( ! in_array( get_post_type( $post_id ), array( 'post', 'resmi-ilanlar' ), true ) ) {
        return;
    }
```

- [ ] **Adım 2: Sözdizimi kontrolü**

```bash
$PHP_BIN -l functions.php
```

Beklenen: `No syntax errors detected`.

- [ ] **Adım 3: Commit**

```bash
git add functions.php
git commit -m "fix: resmi ilan kaydında manşet önbelleğini temizle

clear_custom_post_transients() 'post' dışındaki tiplerde erken çıkıyordu;
bu yüzden resmi ilan manşete eklendiğinde anasayfa eski önbelleği
göstermeye devam ediyordu.

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

### Task 11: Uçtan uca doğrulama

**Files:** — (yalnızca doğrulama)

**Interfaces:**
- Consumes: Task 7-10
- Produces: Bölüm B'nin çalıştığına dair kanıt.

- [ ] **Adım 1: Manşette görünme**

1. `wp-admin` → Resmi İlanlar → Yeni İlan Ekle
2. Başlık gir, **öne çıkarılmış görsel ekle**, "Ana Manşette Göster" işaretle, yayınla
3. `http://localhost/mevzu2/` aç

Beklenen: ilan ana manşet slider'ında görünür.

- [ ] **Adım 2: Çoklu bölge**

Aynı ilanda "Alt Manşette Göster"i de işaretle, güncelle, anasayfayı yenile.

Beklenen: hem ana hem alt manşette görünür.

- [ ] **Adım 3: Önbellek gecikmesi olmadığını doğrula**

Adım 2'den sonra anasayfa **ilk yenilemede** güncellenmiş olmalı. Olmadıysa Task 10 eksik uygulanmıştır.

- [ ] **Adım 4: Görselsiz ilan**

Görseli olmayan yeni bir ilanda "Ana Manşette Göster" işaretle, yayınla.

Beklenen: metabox'ta uyarı görünür, kayıt tamamlanır, ilan anasayfada **çıkmaz**.

- [ ] **Adım 5: Gerileme kontrolü**

Bir haberde (`post`) manşet işaretlemesi yap, kaydet.

Beklenen: mevcut davranış bozulmamış, haber manşette görünüyor.

- [ ] **Adım 6: CHANGELOG'a işle**

`mevzu2-wp/30-Degisiklikler/CHANGELOG.md` içindeki `## [Yayınlanmamış]` altına:

```markdown
### Eklendi

- Resmi ilanlara manşet alanları eklendi. Artık `Sayfa Ayarları` kutusundan
  işaretlenen resmi ilanlar anasayfadaki ilgili manşet bölgesinde
  haberlerle birlikte gösteriliyor.

### Düzeltildi

- Resmi ilan kaydedildiğinde anasayfa manşet önbelleği temizlenmiyordu.
```

```bash
git add mevzu2-wp/30-Degisiklikler/CHANGELOG.md
git commit -m "docs: resmi ilan manşet özelliğini changelog'a ekle

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
```

---

## BÖLÜM D — GitHub ve Sürüm Yayınlama

### Task 12: GitHub bağlantısı ve ilk push

**Files:** — (git yapılandırması)

**Interfaces:**
- Consumes: Task 6'nın `tests/test-sizinti.sh` kapısı
- Produces: `origin` remote'u ve `main` dalının uzak takibi. Task 13 bu bağlantıyı kullanır.

- [ ] **Adım 1: SIZINTI KAPISI — push'tan önce zorunlu**

```bash
./tests/test-sizinti.sh
```

Beklenen: `TUM TESTLER GECTI`.

**DUR:** Bu adım başarısızsa **push yapma.** Depo public; sır bir kez push edilirse git geçmişinden temizlense bile GitHub önbelleğinde ve fork'larda kalır.

- [ ] **Adım 2: Uzak depoyu bağla ve geçmişini al**

```bash
git remote add origin https://github.com/kKerem/mevzu2.git
git fetch origin main
git log --oneline origin/main
```

Beklenen: 3 commit (`Initial commit`, `Create README.md`, `Update README.md`).

- [ ] **Adım 3: Uzak geçmişi yerel geçmişin altına al**

```bash
git rebase --onto origin/main --root main
git log --oneline | tail -8
```

Beklenen: en altta uzaktaki 3 commit, üstünde bizim commit'lerimiz. `LICENSE` ve `README.md` çalışma ağacında mevcut olmalı:

```bash
ls LICENSE README.md
```

Çakışma çıkarsa: `LICENSE` ve `README.md` için **uzak sürümü** koru (`git checkout --theirs`), diğerlerinde yerel sürümü koru.

- [ ] **Adım 4: Yükleme öncesi son kontrol**

```bash
./tests/test-sizinti.sh
git status --short
```

Beklenen: sızıntı testi geçer, çalışma ağacı temiz.

- [ ] **Adım 5: Push**

```bash
git push -u origin main
```

`--force` **kullanılmaz.** Reddedilirse Adım 3'e dön, sebebini incele.

- [ ] **Adım 6: GitHub'da doğrula**

```bash
gh api repos/kKerem/mevzu2/contents --jq '.[].name' | head -20
gh api "repos/kKerem/mevzu2/contents/inc/config-keys.php" 2>&1 | grep -q "Not Found" \
  && echo "DOGRU: config-keys.php uzakta yok" || echo "TEHLIKE: config-keys.php uzakta VAR"
```

Beklenen: tema dosyaları listelenir; `config-keys.php` uzakta **yoktur**.

---

### Task 13: `bin/surum-cikar.sh`

**Files:**
- Create: `bin/surum-cikar.sh`, `tests/test-surum.sh`
- Modify: `style.css` (yalnızca çalıştırıldığında)

**Interfaces:**
- Consumes: Task 2'nin CHANGELOG yapısı, Task 12'nin `origin` bağlantısı, Task 5'in `inc/config-keys.php` dosyası
- Produces: `bin/surum-cikar.sh <versiyon>` — changelog mühürler, sürüm artırır, etiketler, zip'ler, yükler.

- [ ] **Adım 1: Testi yaz**

`tests/test-surum.sh`:

```bash
#!/usr/bin/env bash
# surum-cikar.sh'nin saf fonksiyonlarını doğrular.
set -uo pipefail
cd "$(dirname "$0")/.."
source bin/surum-cikar.sh --sadece-fonksiyonlar

basarisiz=0
kontrol() {
  [ "$2" != "$3" ] && { echo "BASARISIZ: $1 -> '$3' (beklenen '$2')"; basarisiz=1; }
}

kontrol "gecerli surum"   "ok"       "$(surum_dogrula 1.3.8    && echo ok)"
kontrol "harfli surum"    ""         "$(surum_dogrula 1.3.x    && echo ok)"
kontrol "v onekli"        ""         "$(surum_dogrula v1.3.8   && echo ok)"
kontrol "eksik parca"     ""         "$(surum_dogrula 1.3      && echo ok)"

gecici="$(mktemp)"
printf '# Değişiklik Günlüğü\n\n## [Yayınlanmamış]\n\n### Eklendi\n\n- Bir şey.\n' > "$gecici"
changelog_muhurle "$gecici" "1.3.8" "2026-08-06"
grep -q '^## \[1.3.8\] — 2026-08-06$' "$gecici" || { echo "BASARISIZ: muhurleme"; basarisiz=1; }
grep -q '^## \[Yayınlanmamış\]$'      "$gecici" || { echo "BASARISIZ: yeni blok yok"; basarisiz=1; }
rm -f "$gecici"

[ "$basarisiz" -eq 0 ] && echo "TUM TESTLER GECTI"
exit "$basarisiz"
```

- [ ] **Adım 2: Testi çalıştır, başarısız olduğunu gör**

```bash
chmod +x tests/test-surum.sh && ./tests/test-surum.sh
```

Beklenen: `bin/surum-cikar.sh: No such file or directory`.

- [ ] **Adım 3: `bin/surum-cikar.sh` yaz**

```bash
#!/usr/bin/env bash
# Sürüm yayınlama: changelog mühürle -> sürüm artır -> etiketle -> zip -> yükle.
# Kullanım: bin/surum-cikar.sh 1.3.8
set -uo pipefail

SUNUCU="kkerem@104.143.0.74"
UZAK_KOK="/home/kkerem/web/lisans.kkerem.com/public_html/maya"
CHANGELOG="mevzu2-wp/30-Degisiklikler/CHANGELOG.md"

surum_dogrula() {
  [[ "$1" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]
}

changelog_muhurle() {
  local dosya="$1" surum="$2" tarih="$3"
  local gecici; gecici="$(mktemp)"
  awk -v s="$surum" -v t="$tarih" '
    /^## \[Yayınlanmamış\]$/ && !yapildi {
      print "## [Yayınlanmamış]"; print ""; print "## [" s "] — " t
      yapildi = 1; next
    }
    { print }
  ' "$dosya" > "$gecici"
  mv "$gecici" "$dosya"
}

[ "${1:-}" = "--sadece-fonksiyonlar" ] && return 0 2>/dev/null || true

SURUM="${1:-}"
if ! surum_dogrula "$SURUM"; then
  echo "HATA: geçerli bir sürüm verin (örn. 1.3.8)." >&2; exit 1
fi

echo "== 1/7 Sızıntı kapısı =="
./tests/test-sizinti.sh || { echo "HATA: sızıntı testi başarısız." >&2; exit 1; }

echo "== 2/7 Çalışma ağacı temiz mi =="
[ -z "$(git status --porcelain)" ] || { echo "HATA: commit edilmemiş değişiklik var." >&2; exit 1; }

echo "== 3/7 CHANGELOG mühürleniyor =="
changelog_muhurle "$CHANGELOG" "$SURUM" "$(date +%Y-%m-%d)"

echo "== 4/7 style.css sürümü güncelleniyor =="
perl -pi -e "s{^(\s*\*?\s*Version:\s*).*}{\${1}$SURUM}" style.css
grep -nE "^\s*\*?\s*Version:" style.css

git add "$CHANGELOG" style.css
git commit -m "chore: sürüm $SURUM"
git tag "v$SURUM"
git push origin main --follow-tags

echo "== 5/7 Paketleniyor =="
mkdir -p dist
ZIP="$PWD/dist/mevzu2.zip"; rm -f "$ZIP"
STAGE="$(mktemp -d)"
rsync -a --exclude '.git' --exclude 'docs' --exclude 'graphify-out' \
      --exclude 'mevzu2-wp' --exclude 'node_modules' --exclude 'tests' \
      --exclude 'bin' --exclude 'dist' --exclude '.claude' \
      --exclude '*.psd' --exclude '*.zip' --exclude '.DS_Store' \
      ./ "$STAGE/mevzu2/"
cp inc/config-keys.php "$STAGE/mevzu2/inc/config-keys.php"
( cd "$STAGE" && zip -rq "$ZIP" mevzu2 )
rm -rf "$STAGE"

echo "== 6/7 Paket doğrulanıyor =="
unzip -l "$ZIP" | grep -qE "mevzu2/(docs|graphify-out|mevzu2-wp|tests)/" \
  && { echo "HATA: zip'te olmaması gereken klasör var." >&2; exit 1; }
unzip -l "$ZIP" | grep -q "mevzu2/inc/config-keys.php" \
  || { echo "HATA: zip'te config-keys.php yok." >&2; exit 1; }
echo "Paket hazır: $ZIP ($(du -h "$ZIP" | cut -f1))"

echo "== 7/7 Sunucuya yükleniyor =="
# Sunucu SFTP-only kabuk kullanır (Hestia "sftp" SSH Access). Uzak komut
# çalıştırılamaz; mkdir/put/ls hepsi SFTP üzerinden yapılır.
if ! printf 'quit\n' | sftp -o BatchMode=yes -o ConnectTimeout=10 "$SUNUCU" >/dev/null 2>&1; then
  echo "ATLANDI: SFTP erişimi yok. Paket dist/ altında hazır."
  echo "Erişim kurulunca tekrar çalıştırın."
  exit 0
fi

# '-mkdir': dizin zaten varsa hata yut, toplu işi kesme.
sftp -o BatchMode=yes "$SUNUCU" <<SFTP
-mkdir $UZAK_KOK/$SURUM
put $ZIP $UZAK_KOK/$SURUM/mevzu2.zip
SFTP

YEREL_BOYUT="$(wc -c < "$ZIP" | tr -d ' ')"
# sftp 'ls -l' alanları: 1=izinler 2=? 3=uid 4=gid 5=boyut
UZAK_BOYUT="$(printf 'ls -l %s/%s/mevzu2.zip\n' "$UZAK_KOK" "$SURUM" \
  | sftp -o BatchMode=yes "$SUNUCU" 2>/dev/null \
  | awk '/mevzu2\.zip/ { print $5; exit }')"

if [ "$YEREL_BOYUT" != "$UZAK_BOYUT" ]; then
  echo "HATA: boyut uyuşmuyor (yerel $YEREL_BOYUT, uzak ${UZAK_BOYUT:-yok}). Release oluşturulmadı." >&2
  exit 1
fi
echo "Yükleme doğrulandı: $UZAK_BOYUT bayt"

gh release create "v$SURUM" "$ZIP" --title "v$SURUM" \
  --notes "Ayrıntılar için CHANGELOG.md dosyasına bakın."
echo "Sürüm $SURUM yayınlandı."
```

- [ ] **Adım 4: Testi çalıştır, geçtiğini gör**

```bash
chmod +x bin/surum-cikar.sh && ./tests/test-surum.sh
```

Beklenen: `TUM TESTLER GECTI`.

- [ ] **Adım 5: Paketleme adımını kuru çalıştır**

Script'in 5-6. bölümünü elle uygula, zip içeriğini doğrula:

```bash
unzip -l dist/mevzu2.zip | grep -cE "mevzu2/(docs|graphify-out|mevzu2-wp|tests|bin)/"
unzip -l dist/mevzu2.zip | grep "config-keys"
```

Beklenen: ilk komut `0`; ikinci komut `config-keys.php` ve `config-keys.sample.php` satırlarını gösterir.

- [ ] **Adım 6: Commit**

```bash
git add bin/surum-cikar.sh tests/test-surum.sh
git commit -m "feat: sürüm yayınlama script'i

Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
git push origin main
```

- [ ] **Adım 7: SFTP erişimini doğrula**

Erişim 2026-08-06'da kuruldu ve doğrulandı: anahtar panelden yüklendi, kimlik doğrulama çalışıyor, kabuk **SFTP-only**.

```bash
printf 'ls /home/kkerem/web/lisans.kkerem.com/public_html/maya\nquit\n' \
  | sftp -o BatchMode=yes kkerem@104.143.0.74 2>&1 | tail -5
```

Beklenen: mevcut sürüm dizinleri listelenir (`1.2.6` … `1.3.7`).

**Not:** Sunucuda kabuk yok — `ssh kkerem@... 'komut'` çalışmaz, `This service allows sftp connections only.` döner. Bu bir arıza değil, kasıtlı kısıtlama. Yükleme mantığı buna göre SFTP ile yazılmıştır.

---

## Öz-Denetim Notları

**Spec kapsaması:** A1→Task 1, A2→Task 2, A3→Task 4, B1→Task 7, B2→Task 7, B3→Task 8, B4→Task 9, B5→Task 10, B6→(değişiklik gerektirmiyor, Task 11 doğrular), C0→Task 6 Adım 1, C1→Task 5-6, C2→Task 3 Adım 4 (Güvenlik Borçları notu), D1→Task 12, D2→Task 13, D3→Task 13 Adım 7. Spec'in "Doğrulama" bölümü Task 11 ve Task 6 Adım 8'e dağıtıldı.

**Tip tutarlılığı:** `mevzu_key( string $ad, $varsayilan = '' )` — Task 5'te tanımlanır, Task 6'da dört çağrı noktasında aynı imzayla kullanılır. `sistem_bul()` ve `surum_dogrula()`/`changelog_muhurle()` bash fonksiyonları tanımlandıkları script'te ve test dosyalarında aynı adla kullanılır.

**Bilinen sapma:** TDD döngüsü yalnızca saf mantık için (Task 4, 5, 6, 13) kırmızı-yeşil olarak uygulanır. WordPress'e bağımlı davranış (Task 7-10) sözdizimi kapısı + tarayıcı doğrulaması ile kontrol edilir; gerekçe "Test Stratejisi" bölümündedir.
