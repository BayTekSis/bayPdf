# Doğrulama raporu

Tarih: 2026-10-06. Windows üzerinde, yalnızca BayPdf çalışma alanında çalıştırıldı.

Her satırda 31 test / 77 assertion geçti:

| PHP | Laravel | Bağımlılık seçimi |
|---|---|---|
| 8.4.21 | 13.35.0 | Ana geliştirme kurulumu |
| 8.4.21 | 12.69.3 | prefer-stable, temiz kurulum |
| 8.4.21 | 12.69.0 | prefer-lowest, temiz kurulum |
| 8.4.21 | 13.30.0 | prefer-lowest, temiz kurulum |
| 8.3.30 | 12.69.0 | PHP 8.3 platformuna göre prefer-lowest |

Prefer-lowest, Composer güvenlik kısıtları ve geliştirme bağımlılıkları altında çözülen en düşük sürümleri ifade eder; Laravel 12.0/13.0'ın bizzat test edildiği anlamına gelmez. PHP 8.3 + Laravel 13 hücresi yerelde çalıştırılmadı; CI matrisinde tanımlıdır.

Düşük bağımlılık sürümlerinde PHP 8.4, Symfony Translation'ın nullable parametresi için deprecated bildirimi verdi. Testler geçti; vendor değiştirilmedi veya bildirim gizlenmedi. Güncel bağımlılıklarla ana QA bu bildirimi üretmedi.

Testler uygulama anahtarını kendileri üretir; önceden hazırlanmış workbench storage dosyası gerekmez. PHP 8.3 denemesinde gerekli modüller sadece komut parametreleriyle etkinleştirildi; global PHP yapılandırması değiştirilmedi.

## Kalite ve tasarımcı

- `composer qa`: PASS; strict manifest, Pint, PHPStan/Larastan level 5, PHPUnit ve lean distribution validator.
- `npm test`: 2 test PASS.
- `npm run build`: PASS; Vue/CSS/font çıktıları üretildi.
- `npm run test:browser`: 2 Chromium senaryosu PASS; oluşturma, düzenleme, PDF önizleme, yayınlama, klonlama, Almanca ve mobil görünüm.
- Masaüstü ve mobil ekran görüntüleri görsel olarak incelendi.
- `php vendor/bin/boost sync --check`: 35 dosya, drift yok.
- GitHub workflow dosyaları YAML parser ile doğrulandı; GitHub üzerinde henüz çalıştırılmadı.
- Ayrı frontend lint/typecheck scripti yoktur.

## Dağıtım ve sınırlar

`composer archive --format=zip --dir=.artifacts --file=baypdf-dist` ile arşiv oluşturuldu. Runtime kaynakları, migration/config, çeviriler, Blade, derlenmiş arayüz ve lisans belgeleri içerir. Vendor, node_modules, workbench, testler, .env ve yerel artifacts dahil değildir. Arşiv ayrı klasöre açılarak `composer install --no-dev` başarıyla çalıştırıldı.

PDF testleri Unicode font gömme, sayfa boyutları, QR/görsel ve taşma doğrulamasını kapsar. PDF sayfaları bağımsız raster renderer ile ayrıca görsel olarak doğrulanmadı. Veritabanı testleri SQLite üzerinde; MySQL/PostgreSQL ve gerçek çok kullanıcılı yük testi yapılmadı.

Son kaynak incelemesinde eski bir model örneğinin sonradan yayınlanmış sürümü değiştirme ihtimali regresyon testiyle kapatıldı. Yetkisiz erişim, CSRF, dosya yolu geçişi ve uzaktan görsel reddi testlerle doğrulandı.

Jugend repository'si salt okunur tutuldu. Son git kontrolünde master branch'i ve temiz durum korundu. GitHub remote, lisans seçimi ve Packagist yayını bekliyor.
