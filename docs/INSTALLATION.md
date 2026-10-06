# Kurulum ve güncelleme

Bir Laravel uygulamasında:

```bash
composer config repositories.baypdf path C:/laragon/www/bayPdf
composer require bay/baypdf:@dev
php artisan vendor:publish --tag=baypdf-config
php artisan vendor:publish --tag=baypdf-assets
php artisan migrate
```

Path repository yerel geliştirme içindir. Production'da etiketlenmiş bir Composer sürümü kullanın. GitHub/Packagist yayını henüz yoktur. Composer gerektiğinde junction/symlink yerine mirror kullanabilir; paket değişikliklerinin uygulamaya ulaştığını doğrulayın.

## Etkinleştirme

config/baypdf.php:

```php
'enabled' => true,
'path' => 'baypdf',
'middleware' => ['web', 'auth'],
'gate' => 'manage-baypdf',
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
| locale | en; en/de/tr desteklenir |
| disk | local; host diskinin özel kökü olmalıdır |
| asset_prefix | baypdf/assets |
| max_upload_kb | 5120 |
| max_image_pixels | 16000000 |
| font_cache | storage/app/private/baypdf/fonts |

Disk Laravel filesystems.php üzerinden tanımlanır. Public diski seçmeyin. Özel görseller için storage symlink oluşturmayın. Font cache yerel ve PHP sürecince yazılabilir olmalıdır.

Migration'lar provider tarafından yüklenir. Tablolar baypdf_templates ve baypdf_versions adını kullanır. Kullanıcı tablosuna foreign key eklenmez.

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
