---
tags: [sistem, post-type]
---

# Kaynak Alanı

## Ne işe yarar

`post` post tipindeki haberlere, kaynağını belirtmek için tek bir değer atanabilen
bir alan. Etiketler gibi çalışır — daha önce girilen bir kaynak yazılmaya
başlanınca otomatik tamamlama ile önerilir ve aynı isim tekrar term
oluşturmaz — ama etiketlerden farklı olarak bir yazıya yalnızca **tek**
kaynak atanabilir. Örnek: "Karabük Belediyesi".

## İlgili dosyalar

- `inc/kaynak.php` — taksonomi kaydı, metabox, kaydetme, AJAX arama, ön yüz rozeti (tamamı burada)
- `functions.php:10` — dosyanın include edildiği yer
- `sablon/sablon-single-1.php`, `sablon-single-2.php`, `sablon-single-sade.php`,
  `sablon-koseyazisi.php` — dört ulaşılabilir tekil haber şablonunun tamamında
  içerikten hemen sonra `mevzu_kaynak_the_badge()` çağrılır. Kaynak
  atanmamışsa fonksiyon hiçbir şey basmaz.
- `sablon-single-3.php` bilinçli olarak dışarıda bırakıldı — `mevzu_load_single_haber_template()`
  dispatch'inde hiçbir case ona yönlenmiyor (ölü dosya, `options_sablon` değeri
  '3' iken `init` sırasında otomatik 'sade'ye taşınıyor).

## Meta anahtarları

Post meta kullanmaz. Değer, `kaynak` adlı düz (hiyerarşik olmayan) bir
taksonomiye `wp_set_object_terms()` ile yazılır — WordPress'in etiket
mekanizmasıyla aynı alt yapı, farkı kaydetme mantığında.

## Hook'lar

- `add_action('init', 'mevzu_kaynak_register_taxonomy')` — taksonomiyi kaydeder
- `add_action('init', 'mevzu_kaynak_maybe_flush_rewrite', 20)` — `/kaynak/` arşiv linkleri için tek seferlik rewrite flush
- `add_action('add_meta_boxes', 'mevzu_kaynak_add_meta_box')` — tekli-seçim kutusunu ekler
- `add_action('admin_enqueue_scripts', 'mevzu_kaynak_admin_scripts')` — `jquery-ui-autocomplete` kaydını yükler
- `add_action('wp_ajax_mevzu_kaynak_search', 'mevzu_kaynak_ajax_search')` — otomatik tamamlama sorgusu
- `add_action('save_post_post', 'mevzu_kaynak_save_meta_box')` — kaydetme; yalnızca `post` tipinde çalışır

## Bilinen tuzaklar

- Taksonomi `meta_box_cb => false` ile kaydedilir — bu, WordPress'in
  varsayılan çoklu-seçim etiket kutusunu bastırır. Bu satır kaldırılırsa
  hem varsayılan kutu hem de bu dosyanın tekli-seçim kutusu aynı anda
  görünür ve tek-kaynak garantisi bozulur.
- Kaydetme mantığı `wp_set_object_terms($post_id, $deger, 'kaynak', false)`
  çağrısına dayanır — üçüncü parametre (`false` = ekleme değil, değiştirme)
  tek kaynak kısıtını sağlayan asıl satırdır.
- `show_in_rest => false` bilinçli bir seçimdir. Tema genelinde blok editörü
  zaten kapalı (`functions.php:37`), ama açılsa bile REST'e açık bir
  taksonomi Gutenberg'in kendi çoklu-seçim panelini otomatik gösterir ve
  bu, tekli-seçim kutusuyla çakışırdı.
- Aynı kaynağın farklı büyük/küçük harfle girilmesi (`Karabük Belediyesi` /
  `karabük belediyesi`) ayrı term'ler oluşturur — büyük/küçük harf
  duyarsız eşleştirme yapılmıyor.
- Ön yüzde `mevzu_kaynak_the_badge()` ile "Kaynak: X" rozeti gösterilir
  (dört tekil şablonda, içerikten hemen sonra). Rozet linki `/kaynak/{slug}/`
  taksonomi arşivine gider (kayıtta `public => true` olduğu için çalışır).

## Bağlantılı

[[Tema Haritası]]
