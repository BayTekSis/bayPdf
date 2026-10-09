# Kurulum ve güncelleme

Bir Laravel uygulamasında:

```bash
composer config repositories.baypdf vcs https://github.com/BayTekSis/bayPdf
composer require bay/baypdf:dev-master
php artisan vendor:publish --tag=baypdf-config
php artisan vendor:publish --tag=baypdf-assets
php artisan migrate
```

Bu komutlar ilk etiketli sürüm öncesinde GitHub geliştirme branch'ini kurar. `0.1.0` Packagist'e kaydedildikten sonra VCS tanımı olmadan `composer require bay/baypdf:^0.1` kullanın. Production'da etiketli sürümü tercih edin. `migrate` uygulamadaki bütün bekleyen migration'ları çalıştırır.

Yerel paket geliştirmesinde VCS yerine `composer config repositories.baypdf path C:/laragon/www/bayPdf` kullanılabilir; yolu kendi checkout konumuna uyarlayın. Composer junction/symlink yerine mirror kullanırsa paket değişikliklerinin uygulamaya ulaştığını doğrulayın.

## Etkinleştirme

config/baypdf.php:

```php
'enabled' => true,
'path' => 'baypdf',
'middleware' => ['web', 'auth'],
'gate' => 'manage-baypdf',
'scoping' => ['enabled' => false],
'locale' => 'tr',
'disk' => 'local',
```

Host servis sağlayıcısında Gate ve belge türlerini [README](../README.md) örneğindeki gibi kaydedin. Host authentication ve giriş sayfası mevcut olmalıdır. Varsayılan auth guard kullanılır; başka guard için middleware yapılandırmasını değiştirin.

Gate tanımsızsa erişim reddedilir. Bütün API ve görsel yolları Gate ile korunur.

## Yapılandırma

| Anahtar | Varsayılan / davranış |
|---|---|
| enabled | false; tasarımcı route'ları yüklenmez |
| path | baypdf; sayfa/API öneki |
| middleware | web, auth |
| gate | manage-baypdf |
| scoping.enabled | false; shared alan korunur, true ise server-side resolver zorunludur |
| locale | en; en/de/tr desteklenir |
| disk | local; host diskinin özel kökü olmalıdır |
| asset_prefix | baypdf/assets |
| max_upload_kb | 5120 |
| max_image_pixels | 16000000 |
| limits.max_collections | 10 per document type |
| limits.max_collection_fields | 20 per collection schema |
| limits.max_collection_rows | 500 total rows per render |
| limits.max_collection_string_bytes | 5000 per row field |
| limits.max_collection_payload_kb | 1024 across collection input |
| font_cache | storage/app/private/baypdf/fonts |

Disk Laravel filesystems.php üzerinden tanımlanır. Public diski seçmeyin. Özel görseller için storage symlink oluşturmayın. Font cache yerel ve PHP sürecince yazılabilir olmalıdır.

Migration'lar provider tarafından yüklenir. Tablolar baypdf_templates ve baypdf_versions adını kullanır. Kullanıcı tablosuna foreign key eklenmez.

## Opaque scope isolation

Shared kurulum için `scoping.enabled` değerini false bırakın. İzolasyon gerektiğinde host, kendi current-context servisini kullanarak contract'ı bind eder; authoritative scope request body/query parametresinden okunmaz:

```php
use App\Support\CurrentContext;
use BayPdf\Contracts\ScopeResolver;

$this->app->singleton(ScopeResolver::class, function ($app): ScopeResolver {
    return new class($app->make(CurrentContext::class)) implements ScopeResolver {
        public function __construct(private CurrentContext $context) {}

        public function resolve(): ?string
        {
            return $this->context->documentScopeKey();
        }
    };
});
```

Sonra published config içinde `scoping.enabled` true yapılır. Resolver en fazla 191 karakterlik, boş olmayan ve control character içermeyen opaque bir string döndürmelidir. Örneğin `organization:abc` kullanılabilir; BayPdf anahtarın anlamını yorumlamaz. Null/geçersiz sonuç template işlemlerini durdurur.

### Mevcut unscoped template'ler

Scope migration'ı mevcut satırları değiştirmez; `scope_key` nullable eklenir. Scoping açıldığında null satırlar hiçbir scope tarafından görünmez. Host veri sahipliğini dış kayıtlarından doğruladıktan sonra yalnız açıkça eşlenen template ID'lerini bir host migration'ında güncellemelidir:

```php
DB::table('baypdf_templates')
    ->whereNull('scope_key')
    ->whereIn('id', $verifiedTemplateIds)
    ->update(['scope_key' => $verifiedOpaqueScope]);
```

Bu eşleme ownership kararıdır; BayPdf eski satırları current scope'a otomatik atamaz. Scoping etkinleştirilmeden önce bütün gerekli template ve asset eşlemelerini yedekli bir bakım penceresinde tamamlayın.

Legacy asset key'leri de scoped modda varsayılan olarak reddedilir. Published JSON'u değiştirmeden açıkça sahiplenmek için doğrulanmış scope context'i altında her key'i kopyalayın:

```php
use BayPdf\Assets;

app(Assets::class)->adoptLegacy('baypdf/assets/verified-image.png');
```

İşlem orijinali silmez. Doğrulanmış byte'ları current scope fingerprint dizinindeki private legacy mirror'a kopyalar; eski key aynı kalır. Aynı legacy asset gerçekten ortaksa her yetkili scope için ayrı adoption çağrısı gerekir. Ownership bilinmiyorsa kopyalamayın.

## Güncelleme

Yayımlanmış paket için:

```bash
composer update bay/baypdf
php artisan migrate
php artisan vendor:publish --tag=baypdf-assets --force
php artisan optimize:clear
```

Kişiselleştirilmiş config'i --force ile yeniden yayımlamayın. Yeni anahtarları sürüm notlarından ekleyin. Route/config cache deployment sonunda yeniden oluşturulmalıdır.

Kaldırma otomatik veri silmez. Migration rollback iki BayPdf tablosunu ve içeriklerini siler; yalnızca bilinçli veri kaldırma sürecinde uygulanmalıdır.
