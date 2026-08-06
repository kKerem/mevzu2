# Mevzu² Sosyal Otomasyon Modülü

Bu modül, WordPress yazılarınız yayınlandığında (zamanlanmış yazılar dahil) otomatik olarak **Facebook**, **Twitter/X**, **Telegram**, **Instagram** ve bir **Webhook** adresine paylaşır.

> ⚠️ **Önemli:** Bu modül "aç, API bilgilerini gir, çalıştır" mantığıyla çalışır. Facebook, Twitter/X ve Instagram gibi platformlarda resmi uygulama / API anahtarı oluşturma işlemlerini sizin yapmanız gerekir. Aşağıda bu adımlar adım adım anlatılmıştır.

---

## Modülü Aktif Etme

1. WordPress yönetici paneline gidin.
2. **Mevzu² Ayarları → Modüller** menüsünü açın.
3. **Sosyal Otomasyon** kartını bulup aktif edin.
4. Ardından **Mevzu² Ayarları → Sosyal Otomasyon** menüsü görünecektir.

---

## Genel Ayarlar

### Otomatik Paylaşım
Yeni yazılar için aktif platformlar varsayılan olarak işaretli gelir. İsterseniz yazı düzenleme ekranında her yazı için ayrı ayrı seçim yapabilirsiniz.

### Görsel Zorunluluğu
Etkinleştirilirse, öne çıkarılmış görseli olmayan yazılar hiçbir platformda paylaşılmaz. Instagram zaten görsel zorunludur.

### Mesaj Şablonu
Paylaşım mesajını şekillendirmek için şu değişkenleri kullanabilirsiniz:

- `{title}` — Yazı başlığı
- `{excerpt}` — Yazı özeti (ilk ~40 kelime)
- `{url}` — Yazının kalıcı bağlantısı
- `{site_name}` — Site adı

Varsayılan şablon:
```
{title}
{url}
```

---

## Facebook Kurulumu

Facebook paylaşımı için bir **Facebook Sayfası** ve sayfa üzerinden paylaşım yetkisi olan bir **Access Token** gereklidir.

1. [Meta for Developers](https://developers.facebook.com/) adresine gidin ve giriş yapın.
2. Sağ üstten **My Apps → Create App** seçeneğiyle yeni bir uygulama oluşturun.
   - Kullanım amacı olarak **"Other"** → **"Business"** tipini seçebilirsiniz.
3. Uygulamaya **Facebook Login** ve **Pages** ürünlerini ekleyin.
4. Sol menüden **Tools → Graph API Explorer** açın.
5. Sağ üstteki uygulama seçiminden yeni uygulamanızı seçin.
6. **User or Page** alanından paylaşım yapılacak **Sayfayı** seçin.
7. İzinler (Permissions) alanına şunları ekleyin:
   - `pages_read_engagement`
   - `pages_manage_posts`
8. **Generate Access Token** butonuna tıklayın.
9. Elde ettiğiniz token'ı modül ayarlarındaki **Access Token** alanına yapıştırın.
10. Facebook Sayfanızın ID'sini öğrenin:
    - Sayfanızın ayarlarında **Page Transparency** altında bulunabilir veya [Facebook ID Lookup](https://lookup-id.com/) benzeri bir araç kullanabilirsiniz.
    - Yönetici panelde `https://www.facebook.com/1234567890...` şeklindeki sayı dizisi Page ID'dir.
11. Modülde **Bağlantıyı Test Et** butonuna basın.

> 💡 **Not:** Üretim ortamında token'ın süresi dolabilir. Uzun ömürlü token için Meta geliştirici dokümantasyonundaki "Page Access Token" yönergelerini takip edin.

---

## Twitter / X Kurulumu

Twitter/X paylaşımı için **API Key, API Secret, Access Token ve Access Token Secret** değerleri gereklidir.

1. [Twitter Developer Portal](https://developer.twitter.com/en/portal/dashboard) adresine gidin.
2. Bir proje ve uygulama oluşturun.
3. Uygulama ayarlarından **Keys and Tokens** sekmesine geçin.
4. **Consumer Keys** bölümündeki:
   - **API Key** → modüldeki **API Key**
   - **API Secret Key** → modüldeki **API Secret**
5. **Authentication Tokens** bölümünden **Access Token and Secret** oluşturun:
   - **Access Token** → modüldeki **Access Token**
   - **Access Token Secret** → modüldeki **Access Token Secret**
6. Modülde **Bağlantıyı Test Et** butonuna basın.

> ⚠️ **Önemli:** Twitter/X ücretsiz API katmanında yazma (tweet atma) izni genellikle **verilmemektedir**. Paylaşım yapabilmek için **Basic** veya üzeri bir API katmanına sahip olmanız gerekir. Bağlantı testi başarılı olsa bile ücretsiz katmanda gönderim hatası alabilirsiniz.

---

## Telegram Kurulumu

Telegram en kolay çalışan platformdur.

1. Telegram'da **@BotFather** kullanıcısını bulun.
2. `/newbot` komutunu gönderin ve botunuza isim verin.
3. BotFather size şuna benzer bir mesaj gönderir:
   ```
   Use this token to access the HTTP API:
   123456789:ABCdefGHIjklMNOpqrSTUvwxyz
   ```
4. Bu token'ı modüldeki **Bot Token** alanına yapıştırın.
5. Botu paylaşım yapılacak kanala/supergruba ekleyin ve yönetici yapın.
6. Kanalın Chat ID'sini öğrenin:
   - Kanalda herhangi bir mesaja sağ tıklayıp "Copy Link" ile son kısımdaki sayıyı alabilirsiniz.
   - Veya bot token ile şu adrese gidin: `https://api.telegram.org/bot<TOKEN>/getUpdates`
   - Dönen JSON içinde `"chat":{"id":-1001234567890}` şeklindeki negatif sayıyı kopyalayın.
7. Bu ID'yi modüldeki **Chat ID** alanına yapıştırın.
8. **Bağlantıyı Test Et** butonuna basın.

> 💡 **İpucu:** Chat ID genellikle `-100` ile başlar; başındaki eksi işaretini dahil edin.

---

## Instagram Kurulumu

Instagram otomatik paylaşımı için:
- Bir **Instagram Business** veya **Creator** hesabı
- Bu hesabın yönetici olduğu bir **Facebook Sayfası**
- Facebook Graph API üzerinden alınan **Access Token** ve **Instagram User ID**
gereklidir.

1. Instagram hesabınızı bir Facebook Sayfasına bağlayın:
   - Instagram uygulaması → Ayarlar → Hesap → Bağlı Hesaplar → Facebook
   - Veya Facebook Business Manager üzerinden bağlayın.
2. [Meta for Developers](https://developers.facebook.com/) üzerinden bir uygulama oluşturun.
3. Uygulamaya **Instagram Graph API** ürününü ekleyin.
4. Graph API Explorer ile veya uygulama ayarlarından:
   - Sayfa Access Token oluşturun.
   - Instagram Business Account ID'yi öğrenin.
5. Modüldeki **Instagram User ID** ve **Access Token** alanlarını doldurun.
6. **Bağlantıyı Test Et** butonuna basın.

> ⚠️ **Önemli:** Instagram paylaşımı için yazının **öne çıkarılmış görseli** zorunludur. Ayrıca görselin URL'si herkese açık (public) olmalıdır; localhost veya parola korumalı sitelerde çalışmaz.

---

## Webhook Kurulumu

Webhook, belirttiğiniz herhangi bir URL'ye JSON formatında bir POST isteği gönderir. Kendi sunucunuz, Zapier, Make (Integromat), n8n veya benzeri bir servisi hedef alabilir.

1. Modüldeki **Webhook URL** alanına hedef adresi girin.
2. Webhook'u aktif edin.
3. **Bağlantıyı Test Et** butonuna basın.

### Gönderilen JSON Formatı

```json
{
  "site": "Site Adı",
  "site_url": "https://ornek.com",
  "post_id": 123,
  "title": "Yazı Başlığı",
  "excerpt": "Yazı özeti...",
  "url": "https://ornek.com/yazi-basligi",
  "image": "https://ornek.com/wp-content/uploads/2024/01/gorsel.jpg",
  "published_at": "2024-01-15T10:30:00+03:00"
}
```

---

## Zamanlanmış Yazılar ve wp-cron

WordPress zamanlanmış yazıları **wp-cron** adlı dahili mekanizma ile yayına alır. wp-cron, sitenize bir ziyaretçi geldiğinde tetiklenir. Düşük trafikli sitelerde zamanlanmış yazılar gecikebilir.

Eğer zamanlanmış paylaşımların tam saatinde yapılmasını istiyorsanız, sunucunuzda gerçek bir cron görevi ayarlayın:

```bash
*/5 * * * * wget -q -O - https://ornek.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

veya

```bash
*/5 * * * * cd /site/dizini && php wp-cron.php >/dev/null 2>&1
```

---

## Sık Karşılaşılan Hatalar

### "Facebook Page ID ve Access Token gereklidir"
Modülde Facebook alanları boş bırakılmış veya platform aktif edilmemiştir. Platform aktif mi ve token girili mi kontrol edin.

### "Instagram paylaşımı için öne çıkarılmış görsel zorunludur"
Yazının öne çıkarılmış görseli yok. Instagram'da paylaşım için görsel zorunludur.

### Twitter'da "HTTP 403" veya "You do not have permission"
Twitter/X API hesabınızın yazma yetkisi yok veya ücretsiz katmandasınız. API katmanınızı kontrol edin.

### Telegram'da "Chat not found"
Botu kanala/gruba eklememiş olabilirsiniz veya Chat ID yanlıştır. Botu yönetici olarak eklediğinizden emin olun.

### "HTTP 0" veya "cURL error"
Sunucunuzdan dışarıya HTTP isteği yapılamıyor olabilir. Hosting firmanızdan `allow_url_fopen` veya `cURL` desteğini kontrol etmesini isteyin.

---

## Güvenlik Notu

API token, secret gibi değerler WordPress veritabanında düz metin olarak saklanır. Bu değerleri başkalarıyla paylaşmayın ve yönetici erişimini kısıtlayın.
