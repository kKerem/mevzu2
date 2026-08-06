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
