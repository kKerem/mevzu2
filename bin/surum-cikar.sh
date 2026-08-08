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
      --exclude '.superpowers' --exclude '.gitignore' \
      --exclude '*.psd' --exclude '*.zip' --exclude '.DS_Store' \
      ./ "$STAGE/mevzu2/"
cp inc/config-keys.php "$STAGE/mevzu2/inc/config-keys.php"
( cd "$STAGE" && zip -rq "$ZIP" mevzu2 )
rm -rf "$STAGE"

echo "== 6/7 Paket doğrulanıyor =="
# unzip -l çıktısı ÖNCE tamamen bir değişkene alınır, sonra üzerinde grep
# çalıştırılır. Doğrudan `unzip -l | grep -q` borusu kullanılırsa, grep -q
# ilk eşleşmeyi bulunca boruyu erken kapatır; unzip büyük çıktı üretirken
# (binlerce dosya) bu SIGPIPE alıp sıfırdan farklı çıkış koduyla bitebilir,
# pipefail de bunu YANLIŞLIKLA "bulunamadı" sayar — grep doğru cevabı
# vermiş olsa bile. Bu, v1.3.8 ve v1.3.9 yayınlarında gerçekten yaşandı ve
# zip'in içeriği her seferinde doğruydu; sorun yalnızca bu borudaydı.
ZIP_LISTESI="$(unzip -l "$ZIP")"
YASAK="$(grep -oE "mevzu2/(docs|graphify-out|mevzu2-wp|tests|bin|\.superpowers|\.claude|\.git)/" <<< "$ZIP_LISTESI" | sort -u)"
if [ -n "$YASAK" ]; then
  echo "HATA: zip'te olmaması gereken klasör(ler) var:" >&2
  printf '%s\n' "$YASAK" | sed 's/^/    /' >&2
  exit 1
fi
grep -q "mevzu2/inc/config-keys.php" <<< "$ZIP_LISTESI" \
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
#
# DİKKAT: sftp istemcisi verdiğimiz komutu "sftp> ls -l .../mevzu2.zip"
# olarak EKRANA DA BASAR. Sorgu tam dosya yolunu içerdiği için bu echo
# satırı da "mevzu2.zip" ile eşleşir ama yalnızca 4 alanı vardır ($5 boş).
# Desen sadece /mevzu2\.zip/ olursa awk bu echo satırında exit eder ve
# gerçek dosya satırına hiç ulaşmadan boş değer döner — v1.3.9 yayınında
# gerçekten böyle oldu, dosya sunucuda doğru boyuttaydı ama doğrulama
# "uzak: yok" dedi. /^-/ şartı satırın gerçekten `ls -l` çıktısı (izin
# biçimiyle başlayan) olmasını zorunlu kılar, komut echo'sunu eler.
UZAK_BOYUT="$(printf 'ls -l %s/%s/mevzu2.zip\n' "$UZAK_KOK" "$SURUM" \
  | sftp -o BatchMode=yes "$SUNUCU" 2>/dev/null \
  | awk '/^-/ && /mevzu2\.zip/ { print $5; exit }')"

if [ "$YEREL_BOYUT" != "$UZAK_BOYUT" ]; then
  echo "HATA: boyut uyuşmuyor (yerel $YEREL_BOYUT, uzak ${UZAK_BOYUT:-yok}). Release oluşturulmadı." >&2
  exit 1
fi
echo "Yükleme doğrulandı: $UZAK_BOYUT bayt"

# DİKKAT: Zip'i GitHub Release'e EKLEME.
#
# Paket, müşteriye gitmesi için gerçek `inc/config-keys.php` dosyasını
# (lisans imza anahtarı, hava durumu, namaz vakitleri ve nöbetçi eczane
# anahtarları) içerir. GitHub Release'leri herkese açıktır; zip'i oraya
# eklemek bu anahtarları herkesin indirebileceği hale getirir.
#
# Zip'in tek dağıtım kanalı lisans sunucusudur (yukarıdaki 7/7 adımı).
# Release yalnızca sürüm notu taşır.
#
# Bu 2026-08-07'de gerçekten yaşandı: v1.3.8 zip'i yanlışlıkla Release'e
# eklendi, ~1 dakika sonra silindi (indirme sayısı 0).
NOT_METNI="$(sed -n "/^## \[$SURUM\]/,/^## \[/p" "$CHANGELOG" | sed '$d')"
[ -n "$NOT_METNI" ] || NOT_METNI="Ayrıntılar için CHANGELOG.md dosyasına bakın."

gh release create "v$SURUM" --title "v$SURUM" --notes "$NOT_METNI"

# Yanlışlıkla varlık eklenmediğini doğrula.
VARLIK_SAYISI="$(gh release view "v$SURUM" --json assets --jq '.assets | length')"
if [ "${VARLIK_SAYISI:-0}" -ne 0 ]; then
  echo "HATA: Release'e varlık eklenmiş — zip herkese açık olabilir!" >&2
  gh release view "v$SURUM" --json assets --jq '.assets[].name' >&2
  echo "  Hemen kaldırın: gh release delete-asset v$SURUM <ad> --yes" >&2
  exit 1
fi

echo "Sürüm $SURUM yayınlandı (zip yalnızca lisans sunucusunda)."
