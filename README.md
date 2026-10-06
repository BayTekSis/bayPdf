# BayPdf

Belge yerleşimini her değişiklikte PHP koduna müdahale etmeden düzenleyin. BayPdf, Laravel uygulamanıza sürümlü PDF şablonları ve hazır bir görsel tasarımcı ekler.

**Durum:** Yerel geliştirme sürümü. GitHub remote ve Packagist yayını henüz oluşturulmadı. `bay/baypdf` geliştirme paket adıdır; kayıtlı veya yayımlanmış olduğu iddia edilmez.

## Yerelde çalıştırma

BayPdf kaynak klasöründe:

```bash
composer install
npm ci
npm run build
composer workbench:prepare
composer serve
```

[http://127.0.0.1:8127/baypdf](http://127.0.0.1:8127/baypdf) adresini açın. Workbench kendi SQLite dosyasını kullanır ve yalnızca loopback istekleri kabul eder. Geliştirme kullanıcısı sadece workbench'tedir.

## Gereksinimler

| Kullanım | Gereksinim |
|---|---|
| Uygulamada çalıştırma | PHP 8.3+, Laravel 12 veya 13, GD, mbstring |
| Veritabanı | Laravel bağlantısı ve ilgili PDO sürücüsü |
| Dosyalar | Özel storage diski ve yazılabilir font cache dizini |
| Paket geliştirme | Composer 2, Node 22.12+ veya 24+, npm |
| Test | SQLite, Orchestra Testbench ve geliştirme bağımlılıkları |

**Hazır paketi kullanan uygulamada Node/npm gerekmez.** Derlenmiş Vue, CSS ve fontlar Composer paketine dahildir. package.json özeldir; npm'e ayrı paket yayımlanmaz.

## Başka bir Laravel uygulamasına kurulum

Henüz Packagist yayını olmadığı için yerel Composer repository kullanın. Aşağıdaki komutları **paketi kullanacak uygulamada** çalıştırın:

```bash
composer config repositories.baypdf path C:/laragon/www/bayPdf
composer require bay/baypdf:@dev
php artisan vendor:publish --tag=baypdf-config
php artisan vendor:publish --tag=baypdf-assets
php artisan migrate
```

Komutlar hedef uygulamaya dosya ve iki baypdf_* tablosu ekler. Mevcut veritabanını sıfırlamaz. Ayrıntılar [kurulum rehberinde](docs/INSTALLATION.md).

config/baypdf.php içinde enabled değerini true yapın. Tasarımcı varsayılan kapalıdır. Açıldığında web, auth ve manage-baypdf Gate kontrolü uygulanır. Gate'i mevcut izin sisteminize bağlayın:

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

Gate::define('manage-baypdf', fn (User $user): bool =>
    $user->can('manage-documents')
);
```

manage-documents uygulamanızda tanımlanması gereken izin/policy yeteneğidir. BayPdf herhangi bir kullanıcıyı otomatik yetkilendirmez. Giriş yapmış, bu izne sahip kullanıcılar /baypdf sayfasını açabilir.

## Kendi değişkenlerinizi tanımlama

Uygulamanızın servis sağlayıcısının boot metodunda:

```php
use BayPdf\DocumentTypes;

public function boot(DocumentTypes $types): void
{
    $types->register('certificate', 'Katılım belgesi', [
        [
            'key' => 'recipient.name',
            'label' => 'Katılımcı adı',
            'group' => 'Katılımcı',
            'type' => 'text',
            'required' => true,
            'example' => 'Ayşe Yılmaz',
        ],
        [
            'key' => 'issued.date',
            'label' => 'Düzenleme tarihi',
            'type' => 'date',
            'example' => '2026-10-06',
            'format' => 'd.m.Y',
        ],
    ]);
}
```

Tasarımcıdan **Yeni şablon** seçin, belge türünü belirleyin ve değişken düğmesine tıklayarak sayfaya ekleyin. Konumu sürükleyerek, ok tuşlarıyla veya milimetre alanlarıyla düzenleyin. Taslağı kaydedin, PDF önizlemesini kontrol edin ve sürümü yayınlayın.

## Yayınlanan sürümden PDF üretme

Uygulamanızın yetkilendirdiği controller/job içinde:

```php
use BayPdf\Models\TemplateVersion;
use BayPdf\TemplateManager;

// $versionId, uygulamanızın seçip yetkilendirdiği yayınlanmış sürümdür.
$version = TemplateVersion::query()->findOrFail($versionId);

$pdf = app(TemplateManager::class)->render($version, [
    'recipient' => ['name' => 'Ayşe Yılmaz'],
    'issued' => ['date' => '2026-10-06'],
]);

return response($pdf, 200, [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'attachment; filename="certificate.pdf"',
    'Cache-Control' => 'private, no-store',
]);
```

BayPdf veriyi uygulamanızın modellerinden otomatik çekmez; uygulama izin kontrolünü yapıp veriyi sağlar. Önizleme yalnızca örnek değerleri kullanır. Yayınlanmış sürüm değişmez; düzenleme için yeni taslak klonlanır.

## Kapsam ve sınırlar

- Metin, değişken, PNG/JPEG, QR, çizgi ve dikdörtgen; katman sırası, gizleme, çoğaltma ve undo/redo.
- A4, A5, Letter; yatay/dikey; sabit tek sayfa. Otomatik çok sayfa/tablo akışı yoktur.
- DejaVu Sans normal/kalın/italik fontları; DE/TR/EN tasarımcı metinleri.
- Taşan metin sessizce kesilmez, doğrulama hatası döner.
- HTML/PDF şablon içe aktarma, SVG/WebP ve uzaktan görsel indirme desteklenmez.
- Tek ortak şablon alanı; yerleşik tenant izolasyonu yoktur.
- PDF imzası, QR doğrulama servisi ve belge arşivi host uygulamanın sorumluluğundadır.

## Dokümantasyon

Bu rehberler kaynak repository'sinde bulunur; hafif Composer dağıtım arşivine docs klasörü dahil edilmez.

| Rehber | İçerik |
|---|---|
| [Kurulum](docs/INSTALLATION.md) | Composer, yapılandırma, erişim ve güncelleme |
| [Değişkenler](docs/VARIABLES.md) | Türler, örnekler, zorunlu alanlar ve snapshot |
| [Şablonlar ve API](docs/TEMPLATES.md) | JSON yerleşimi, sürümler ve PHP API |
| [Güvenlik](docs/SECURITY.md) | Yetkiler, özel dosyalar, sınırlar |
| [Geliştirme](docs/DEVELOPMENT.md) | Testbench, skills, testler ve build |
| [GitHub ve dağıtım](docs/PUBLISHING.md) | Repository, CI, sürümleme, Packagist |
| [Sorun giderme](docs/TROUBLESHOOTING.md) | Kurulum ve çıktı hataları |
| [Mimari](docs/ARCHITECTURE.md) | Teknik kararlar |
| [Doğrulama](docs/VERIFICATION.md) | Çalıştırılan kontroller ve ortam |

## Geliştirme kontrolleri

```bash
composer qa
npm test
npm run build
composer workbench:prepare
npx playwright install chromium
npm run test:browser
```

Katkı: [CONTRIBUTING.md](CONTRIBUTING.md). Geçmiş: [CHANGELOG.md](CHANGELOG.md). Kullanıcı API'si: [PUBLIC_API.md](PUBLIC_API.md).

## Lisans ve kaynaklar

BayPdf'nin dağıtım lisansı henüz seçilmedi; composer.json bu nedenle proprietary belirtir. Açık kaynak kullanım izni verilmiş değildir. [LICENSE](LICENSE) ve [üçüncü taraf bildirimleri](THIRD_PARTY_NOTICES.md) geçerlidir.

PDF: tFPDF / FPDF ailesi. QR: endroid/qr-code. Tasarımcı: Vue. Font: DejaVu. Geliştirme skills: sandermuller/package-boost-laravel ve sandermuller/boost-skills.
