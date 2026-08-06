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
