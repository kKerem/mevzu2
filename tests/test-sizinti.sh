#!/usr/bin/env bash
# Sırların takip edilen dosyalara VE git geçmişine sızmadığını doğrular.
#
# Bu script hiçbir sır değerini kendi içinde TAŞIMAZ — aranacak değerleri
# git dışı `inc/config-keys.php` dosyasından türetir. Böylece testin kendisi
# bir sızıntı kaynağı olmaz.
set -uo pipefail
cd "$(dirname "$0")/.."

KEYS_FILE="inc/config-keys.php"

PHP_BIN="$(ls -d /Applications/MAMP/bin/php/php*/bin/php 2>/dev/null | tail -1)"
[ -x "${PHP_BIN:-}" ] || PHP_BIN="$(command -v php || true)"
if [ -z "${PHP_BIN:-}" ]; then
  echo "BASARISIZ: PHP bulunamadı, sır listesi türetilemiyor." >&2
  exit 1
fi

if [ ! -f "$KEYS_FILE" ]; then
  echo "BASARISIZ: $KEYS_FILE yok — sır listesi türetilemiyor." >&2
  echo "  Bu dosya git dışıdır; kurulum için inc/config-keys.sample.php dosyasına bakın." >&2
  exit 1
fi

# 16 karakter ve üzeri tüm string değerleri sır adayı say (iç içe dizileri de tara).
# PHP hatası sessizce boş listeye dönüşüp testi yanlışlıkla geçirmesin diye
# stderr ayrılır ve çıktı biçimi doğrulanır.
PHP_HATA="$(mktemp)"
SIRLAR="$("$PHP_BIN" -r '
// config-keys.php ABSPATH koruması taşır; CLI için tanımla.
if ( ! defined( "ABSPATH" ) ) { define( "ABSPATH", __DIR__ . "/" ); }
$k = require "inc/config-keys.php";
$arr = (array) $k;               // referansla geçilebilmesi için değişkene al
$out = [];
array_walk_recursive($arr, function ($v) use (&$out) {
    if (is_string($v) && strlen($v) >= 16) { $out[] = $v; }
});
echo implode("\n", array_unique($out));
' 2>"$PHP_HATA")"
PHP_CIKIS=$?

if [ "$PHP_CIKIS" -ne 0 ] || [ -s "$PHP_HATA" ]; then
  echo "BASARISIZ: sır listesi türetilirken PHP hatası oluştu:" >&2
  sed 's/^/    /' "$PHP_HATA" >&2
  rm -f "$PHP_HATA"
  exit 1
fi
rm -f "$PHP_HATA"

ADET="$(printf '%s\n' "$SIRLAR" | grep -c . || true)"

# Beklenen en az sır sayısı. Türetme bozulup listeyi kısaltırsa test sessizce
# geçmek yerine burada durur.
: "${BEKLENEN_SIR_SAYISI:=7}"
if [ "$ADET" -lt "$BEKLENEN_SIR_SAYISI" ]; then
  echo "BASARISIZ: yalnızca $ADET sır türetildi, en az $BEKLENEN_SIR_SAYISI bekleniyordu." >&2
  echo "  Türetme bozulmuş olabilir — bu testin geçmesi güvenli değil." >&2
  exit 1
fi

# Her satır tek parça bir sır olmalı; boşluk içeren satır hata mesajı demektir.
if printf '%s\n' "$SIRLAR" | grep -q '[[:space:]]'; then
  echo "BASARISIZ: türetilen listede boşluk içeren satır var — PHP hata çıktısı olabilir." >&2
  exit 1
fi

echo "== $ADET sır değeri türetildi (değerler yazdırılmaz) =="

hata=0

echo "== 1/4 Takip edilen dosyalarda sır taraması =="
while IFS= read -r sir; do
  [ -z "$sir" ] && continue
  if git grep -n -F "$sir" -- . >/dev/null 2>&1; then
    echo "BASARISIZ: bir sır takip edilen dosyalarda bulundu:"
    git grep -l -F "$sir" -- . | sed 's/^/    /'
    hata=1
  fi
done <<< "$SIRLAR"

echo "== 2/4 Git geçmişinde sır taraması =="
while IFS= read -r sir; do
  [ -z "$sir" ] && continue
  for c in $(git rev-list --all); do
    if git grep -q -F "$sir" "$c" -- . 2>/dev/null; then
      echo "BASARISIZ: bir sır git geçmişinde bulundu: $(git log -1 --format='%h %s' "$c")"
      hata=1
      break
    fi
  done
done <<< "$SIRLAR"

echo "== 3/4 config-keys.php yok sayılıyor mu =="
if ! git check-ignore -q "$KEYS_FILE"; then
  echo "BASARISIZ: $KEYS_FILE yok sayılmıyor."
  hata=1
fi

echo "== 4/4 config-keys.php takip ediliyor mu =="
if git ls-files --error-unmatch "$KEYS_FILE" >/dev/null 2>&1; then
  echo "BASARISIZ: $KEYS_FILE git tarafından takip ediliyor."
  hata=1
fi

if [ "$hata" -eq 0 ]; then
  echo "TUM TESTLER GECTI"
else
  echo "SIZINTI TESTI BASARISIZ — push YAPMAYIN." >&2
fi
exit "$hata"
