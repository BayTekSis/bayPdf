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

Koordinatlar ve boyutlar mm; font_size punto. Sayfa A4 (210×297), A5 (148×210) veya Letter (215.9×279.4). Yatay yön ölçüleri ters çevirir. Öğeler array sırasıyla çizilir; son öğe en önde. Legacy belge en fazla 100 öğe kabul eder; schema v2 fixed öğe, tablo ve trailing öğelerin toplamını `limits.max_layout_elements` ile sınırlar (varsayılan 200).

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

Görsel oranı korunarak kutuya sığar. Tam sayfa görsel ilk katmana alınarak arka plan yapılabilir. Orijinal dosya değişmez; otomatik crop yoktur. Scoped modda static asset save sırasında current scope'a ait olmalıdır; image variable key'i render sırasında aynı denetimden geçer.

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

Collection değişkeni bulunan belge türlerinde tasarımcı `Advanced / data` bölümü gösterir. Kullanıcı bu açık eylemle legacy taslağı schema v2'ye yükseltir; source collection, sütun eşlemesi/etiketi/genişliği/hizası, repeated table header ve temel table style alanlarını yönetir. Fixed öğeler first/all/continuation/last sayfa kuralı ve page/header/footer region seçebilir. Page number ve table sonrasını izleyen text/variable blokları aynı panelden eklenir. Legacy taslaklar kullanıcı collection table eklemedikçe otomatik dönüştürülmez.

## Layout schema v2: collection table and flow

Legacy documents omit `schema_version` and remain fixed single-page layouts. Multi-page behavior requires explicit `schema_version: 2`; BayPdf does not rewrite stored legacy JSON.

```php
[
    'schema_version' => 2,
    'page' => ['size' => 'A4', 'orientation' => 'portrait'],
    'elements' => [
        [
            'id' => 'header', 'type' => 'text', 'region' => 'header', 'repeat' => 'all',
            'x' => 15, 'y' => 10, 'width' => 180, 'height' => 8,
            'content' => 'Document title', 'font_size' => 10,
        ],
        [
            'id' => 'page-number', 'type' => 'page_number', 'region' => 'footer', 'repeat' => 'all',
            'x' => 140, 'y' => 281, 'width' => 55, 'height' => 8,
            'content' => 'Page {current} / {total}', 'font_size' => 8, 'align' => 'R',
        ],
    ],
    'flow' => [
        'first_top' => 30,
        'continuation_top' => 25,
        'bottom' => 275,
        'gap' => 4,
        'table' => [
            'id' => 'items-table',
            'type' => 'collection_table',
            'source' => 'items',
            'x' => 15,
            'width' => 180,
            'repeat_header' => true,
            'columns' => [
                ['field' => 'description', 'label' => 'Description', 'width' => 105, 'align' => 'L'],
                ['field' => 'quantity', 'label' => 'Quantity', 'width' => 30, 'align' => 'R'],
                ['field' => 'total', 'label' => 'Total', 'width' => 45, 'align' => 'R'],
            ],
            'header' => ['font_size' => 9, 'font_style' => 'B', 'color' => '#172b29', 'fill' => '#e5edde', 'padding' => 2, 'border' => true],
            'row' => ['font_size' => 9, 'font_style' => '', 'color' => '#172b29', 'fill' => null, 'padding' => 2, 'border' => true],
        ],
        'trailing' => [[
            'id' => 'summary', 'type' => 'variable', 'variable' => 'document.summary',
            'x' => 15, 'width' => 180, 'height' => 20, 'gap_before' => 5,
            'font_size' => 10,
        ]],
    ],
]
```

`flow` supports one primary collection table followed by a bounded list of ordinary trailing elements. This deliberate limit covers header → table → summary/notes/signature-style blocks without becoming a general HTML/CSS layout engine. Multiple independent flowing collections are not supported in schema v2.

Column widths are millimetres and must sum exactly to table `width`. Each column maps to one field in the source collection. Header and row styles control font, color, fill, padding and cell border. Text wraps by measured tFPDF font width; every cell in a row uses the tallest measured cell height. No cell content is silently truncated.

The first page starts at `first_top`; continuation pages start at `continuation_top`; `bottom` reserves footer space. Table headers render on page one and repeat on continuation pages when `repeat_header` is true. A row that cannot fit an otherwise empty continuation area fails with `data.<collection>.<row>` and does not loop.

Trailing elements use the existing text, variable, image, QR, line and rectangle contract plus `page_number`. Their Y position is calculated from the actual table end. A block that does not fit moves as a whole to the next page; a block taller than the usable page fails. `gap_before` overrides the flow `gap`.

Hidden trailing elements reserve no gap or height and do not create pages. A `text` element bound through `variable` accepts scalar text-compatible types; collection values must use the table source.

Absolute page elements accept `region` (`page`, `header`, `footer`) and `repeat` (`first`, `all`, `continuation`, `last`). Header elements must end above both flow tops; footer elements must start at or below flow bottom. `page_number` uses `{current}` and `{total}` from the completed pagination plan, so the host does not register page variables and PDF bytes are not patched after generation.

Explicit `schema_version` yalnız integer `1` veya `2` olabilir; string, float, null ve desteklenmeyen sürümler validation hatası verir. Sürüm alanı olmayan legacy belgeler desteklenmeye devam eder.
