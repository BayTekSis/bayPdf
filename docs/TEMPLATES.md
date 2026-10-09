# Şablonlar ve PHP API

```php
use BayPdf\TemplateManager;

$manager = app(TemplateManager::class);
$template = $manager->create('Katılım belgesi', 'certificate');
$draft = $template->versions->first();

$draft = $manager->save($draft, [
    'page' => ['size' => 'A4', 'orientation' => 'portrait'],
    'elements' => [[
        'id' => 'recipient', 'type' => 'variable', 'variable' => 'recipient.name',
        'x' => 20, 'y' => 60, 'width' => 170, 'height' => 30,
        'font_size' => 24, 'font_style' => 'B',
        'color' => '#173b34', 'align' => 'C',
    ]],
], $draft->lock_version);

$published = $manager->publish($draft, $draft->lock_version);
$pdf = $manager->render($published, ['recipient.name' => 'Ayşe Yılmaz']);
```

Örnek, README'deki certificate registry tanımını gerektirir. Üretimden önce host kullanıcının belgeye/veriye erişimini doğrular.

## Yerleşim

Koordinatlar ve boyutlar mm; font_size punto. Sayfa A4 (210×297), A5 (148×210) veya Letter (215.9×279.4). Yatay yön ölçüleri ters çevirir. Öğeler array sırasıyla çizilir; son öğe en önde. En fazla 100 öğe.

`elements`, sıfırdan başlayan ardışık indeksli bir liste olmalıdır. İsimli veya aralıklı anahtarlar doğrulama hatası verir; filtrelenmiş PHP dizilerini `array_values()` ile yeniden indeksleyin.

| type | Veri |
|---|---|
| text | content düz metni |
| variable | variable anahtarı; image türü hariç |
| image | asset anahtarı veya image değişkeni |
| qr | content veya variable |
| line | x,y'den x+width,y+height'a çizgi |
| rectangle | color kenarlık, isteğe bağlı fill |

Ortak alanlar id,type,x,y,width,height,hidden. Metin alanları font_size,font_style,align,color. Stiller boş/B/I/BI, hizalama L/C/R, renkler #RRGGBB. Fill null ise şeffaf; kenarlık 0.3 mm.

Görsel oranı korunarak kutuya sığar. Tam sayfa görsel ilk katmana alınarak arka plan yapılabilir. Orijinal dosya değişmez; otomatik crop yoktur.

Metin satırlara sarılır. Sığmazsa 422 verir; kutuyu büyütün veya fontu küçültün. DejaVu DE/TR/EN desteği sağlar; Arapça şekillendirme, emoji ve CJK kapsamı taahhüt edilmez.

## Sürümler

| Metot | Davranış |
|---|---|
| create(name,type) | Şablon ve ilk taslak, transaction |
| save(version,document,expectedLock) | Taslak kaydı, lock_version artışı |
| preview(version) | Snapshot örnekleriyle PDF |
| publish(version,expectedLock) | Önizlemeyi doğrular, sürümü kilitler |
| cloneDraft(version) | Şema/yerleşim kopyası, yeni numara |
| render(version,data) | Yayınlanmış sürümden PDF byte string |

Eski lock_version 409 üretir. Yeniden yükleyin; başkasının kaydını otomatik ezmeyin. Doğrudan SQL/bulk Eloquent model korumalarını atlar; yazımlar için TemplateManager kullanın.

Scoping etkinse TemplateManager current opaque scope'u server-side resolver'dan alır. Create edilen template bu scope'u taşır; save, publish, clone, preview ve render yalnız aynı scope'un version'larını kabul eder. TemplateVersion ayrı bir scope alanı taşımaz ve başka Template'e reassociate edilemez. Direct model sorguları package servisinin scope kontrolünü atlayabileceğinden recommended public path değildir.

Veritabanı olmadan: `app(BayPdf\PdfRenderer::class)->render($document, $data, $variables)`. Bu düşük seviye API yayın durumu denetlemez. variables, DocumentTypes üzerinden doğrulanmış şemadır.

## Tasarımcı API

Sayfa yolu altında api/catalog, api/templates, api/templates/{id}, api/versions/{id}, sürümün publish/clone/preview yolları ve api/assets bulunur. Session/CSRF kullanılır. Bunlar tasarımcının iç entegrasyonudur; uygulama kodu PHP API'yi tercih etmelidir.

Canvas yaklaşık düzenleme görünümüdür. Gerçek satır sarma, biçimlendirilmiş değişken ve QR çıktısı server PDF önizlemesinde görülür.
