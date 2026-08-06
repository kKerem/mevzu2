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
