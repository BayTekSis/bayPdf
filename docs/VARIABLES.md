# Değişkenler

```php
$types->register('quote', 'Teklif', [
    ['key' => 'customer.name', 'label' => 'Müşteri', 'type' => 'text',
     'required' => true, 'example' => 'Ayşe Yılmaz'],
    ['key' => 'quote.total', 'label' => 'Toplam', 'type' => 'money',
     'currency' => 'EUR', 'decimals' => 2, 'example' => 1250.5],
    ['key' => 'quote.date', 'label' => 'Tarih', 'type' => 'date',
     'format' => 'd.m.Y', 'example' => '2026-10-06'],
    ['key' => 'note', 'label' => 'Not', 'type' => 'text', 'default' => 'Teşekkürler'],
]);
```

Anahtarlar küçük harf, rakam, alt çizgi ve noktayla tanımlanır. customer.name iç içe array veya düz anahtarla beslenebilir:

```php
$data = ['customer' => ['name' => 'Ayşe Yılmaz'], 'quote.total' => 1250.5];
```

İki biçim birlikte verilirse düz anahtar önceliklidir. Model/nesne verilmez; host değerleri array olarak hazırlar. Değişken şablon içinde PHP, SQL veya JavaScript çalıştırmaz.

| Alan | Açıklama |
|---|---|
| key | Belge türü içinde benzersiz sabit anahtar |
| label | Görünen ad; host locale'e göre çevirebilir |
| group | Tasarımcıda gruplama |
| type | text, date, number, money, image, qr |
| required | Değer yoksa doğrulama hatası |
| example | Önizleme değeri; gerçek müşteri verisi olmamalı |
| default | Anahtar hiç verilmediyse kullanılan değer |
| format | Tarih biçimi; varsayılan d.m.Y |
| currency | Üç büyük harfli para kodu; varsayılan EUR |
| decimals | number/money için 0–6; varsayılan 2 |

En fazla 100 değişken. Veriler scalar ve en fazla 5000 karakter olmalıdır. Sıfır geçerlidir. Açık null/boş metin default yerine boş kabul edilir; required ise hata verir.

`register()` çağrısındaki değişken şeması sıfırdan başlayan ardışık indeksli bir liste olmalıdır. İsimli veya aralıklı anahtarlar kabul edilmez; gerekirse `array_values()` kullanın. Bu kural, anahtarlı veri dizisine (`$data`) uygulanmaz.

Tarih kesin YYYY-MM-DD biçimindedir; imkânsız tarihler reddedilir. Para ana para birimindedir, kuruş/cent değildir. Biçim 1,250.50 EUR olur. Farklı yerel biçim için host değeri biçimlendirip text türü olarak gönderebilir.

image izin verilen disk/önek içindeki asset anahtarını taşır. URL/base64 kabul edilmez. qr içeriği QR olarak çizer; sınır 1000 byte. Bağlantının işlevini host sağlar.

## Collection records

Generic tekrarlanan veriler `collection` olarak kaydedilir. Collection yalnız bounded scalar record listesi taşır; nested collection veya object kabul edilmez:

```php
$types->register('commercial_document', 'Commercial document', [
    [
        'key' => 'items',
        'label' => 'Items',
        'type' => 'collection',
        'max_rows' => 100,
        'fields' => [
            ['key' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => true],
            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'decimals' => 3],
            ['key' => 'unit_price', 'label' => 'Unit price', 'type' => 'money', 'currency' => 'EUR'],
            ['key' => 'service_date', 'label' => 'Date', 'type' => 'date', 'format' => 'd.m.Y'],
        ],
        'example' => [[
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => 125.5,
            'service_date' => '2026-10-09',
        ]],
    ],
]);
```

Render input is a zero-indexed list of associative rows:

```php
[
    'items' => [
        ['description' => 'Consulting', 'quantity' => 2, 'unit_price' => 125.5, 'service_date' => '2026-10-09'],
    ],
]
```

Supported field types are text, date, number, and money. Field keys are flat identifiers; values must be scalar. Unknown fields, numeric/list rows, nested arrays, PHP objects, closures, invalid dates/numbers, and missing required fields produce path-based validation errors without echoing the submitted value. HTML-like strings remain plain text and are never executed.

Global defaults allow 10 collections per document type, 20 fields per collection, 500 total rows per render, 5000 bytes per field, and 1024 KiB total collection JSON payload. `max_rows` can lower the per-collection row cap but cannot raise the global total. Collection nesting depth is fixed at one collection of scalar records.

Example rows follow the same validation and formatting contract. With no example, an optional collection previews as an empty list. The entire collection definition and examples are copied into each TemplateVersion snapshot and preserved when cloning.

## Şema snapshot'ı

Şablon oluşturulurken şema sürüme kopyalanır. Klonlanan taslak aynı şemayı korur. Registry değişikliği geçmiş sürümleri değiştirmez; yeni şema için yeni şablon oluşturun.

Sürümdeki bütün required değişkenler üretimde gereklidir; sayfada kullanılmamaları koşulu kaldırmaz. Yayın öncesi PDF örnek veriyle üretildiğinden required alanlara geçerli example/default sağlanmalıdır.
