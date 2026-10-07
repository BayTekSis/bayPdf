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

## Şema snapshot'ı

Şablon oluşturulurken şema sürüme kopyalanır. Klonlanan taslak aynı şemayı korur. Registry değişikliği geçmiş sürümleri değiştirmez; yeni şema için yeni şablon oluşturun.

Sürümdeki bütün required değişkenler üretimde gereklidir; sayfada kullanılmamaları koşulu kaldırmaz. Yayın öncesi PDF örnek veriyle üretildiğinden required alanlara geçerli example/default sağlanmalıdır.
