# Doğrulama raporu

## Kapsamlı kontrol — 2026-10-07

Son kod üzerinde `composer qa`: **34 test / 80 assertion**, Pint, PHPStan ve dağıtım doğrulayıcısı PASS. `npm.cmd test`: **2 test**; production build ve Chromium: **4 senaryo PASS**. Ayrı lint/typecheck scripti yoktur.

Beş sorun önce başarısız regresyon testleriyle doğrulandı, sonra düzeltildi: null byte içeren tarihte ValueError, liste olmayan öğe ve değişken şemalarının kabulü, `constructor`/`__proto__` grup adlarında arayüzün bozulması ve kalabalık kütüphanenin düzenleme araçlarını ekran dışına itmesi. Mobil senaryo 120 karakterlik adla yatay taşmayı da denetler. Güncel masaüstü, kalabalık liste ve mobil ekran görüntüleri incelendi.

`.artifacts/baypdf-audit.zip`: 36 giriş; geliştirme/özel dosya yok. Arşivdeki değişen kaynak ve build dosyalarının hash değerleri çalışma ağacıyla aynı. Ayrı dizinde `composer install --no-dev --no-scripts`, platform, autoload ve asset kontrolleri PASS; Testbench kurulu değil. Composer runtime audit ve npm audit bildirim bulmadı. Boost kontrolü 35 dosyada drift bulmadı.

Bağımsız agent incelemesinin bulduğu değişken listesi sorunu da giderildi. Codex CLI incelemesi çalışmadı: `Codex CLI with exec review is required`. Bu tur PHP 8.4/Laravel 13/SQLite ve Chromium ile sınırlıdır; PHP/Laravel matrisi, MySQL/PostgreSQL, yük testi, PDF raster kontrolü ve son commit'in uzak CI sonucu doğrulanmadı. Yerel paket hazır; yayın için DURUM.md'deki dış adımlar bekleniyor.

## Önceki doğrulamalar

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
- GitHub workflow dosyaları YAML parser ile doğrulandı. Bu ilk yerel doğrulama sırasında uzak CI henüz çalıştırılmamıştı; güncel yayın kontrolü aşağıdadır.
- Ayrı frontend lint/typecheck scripti yoktur.

## Dağıtım ve sınırlar

`composer archive --format=zip --dir=.artifacts --file=baypdf-dist` ile arşiv oluşturuldu. Runtime kaynakları, migration/config, çeviriler, Blade, derlenmiş arayüz ve lisans belgeleri içerir. Vendor, node_modules, workbench, testler, .env ve yerel artifacts dahil değildir. Arşiv ayrı klasöre açılarak `composer install --no-dev` başarıyla çalıştırıldı.

PDF testleri Unicode font gömme, sayfa boyutları, QR/görsel ve taşma doğrulamasını kapsar. PDF sayfaları bağımsız raster renderer ile ayrıca görsel olarak doğrulanmadı. Veritabanı testleri SQLite üzerinde; MySQL/PostgreSQL ve gerçek çok kullanıcılı yük testi yapılmadı.

Son kaynak incelemesinde eski bir model örneğinin sonradan yayınlanmış sürümü değiştirme ihtimali regresyon testiyle kapatıldı. Yetkisiz erişim, CSRF, dosya yolu geçişi ve uzaktan görsel reddi testlerle doğrulandı.

Jugend repository'si ilk geliştirme sırasında salt okunur tutuldu; o kontrol sırasında master branch'i ve temiz durum korundu.

## Yayın hazırlığı — 2026-10-07

- `composer qa`: 31 test / 77 assertion, Pint, PHPStan ve dağıtım doğrulayıcısı PASS.
- `npm.cmd ci`, `npm.cmd test` (2 test), `npm.cmd run build`: PASS. `public/` çıktısı mevcut commit ile aynı.
- `composer workbench:prepare` ve `npm.cmd run test:browser`: PASS; 2 Chromium senaryosu.
- `composer audit --locked --no-dev`: bilinen güvenlik bildirimi yok. npm kurulum denetimi: 0 vulnerability.
- `php vendor/bin/boost sync --check`: 35 dosyada drift yok.
- `.artifacts/baypdf-release-check.zip` dışlama kontrolü PASS; özel/geliştirme dosyaları yok. Ayrı dizinde `composer install --no-dev --no-scripts`, platform gereksinimleri ve runtime autoload/asset kontrolü PASS. Testbench kurulmadığı doğrulandı.
- GitHub workflow YAML dosyaları parse edildi. Yeni `distribution` işi arşiv dışlamalarını, PHP 8.3 üzerinde dev bağımlılıkları olmadan kurulumu ve runtime dosyalarını kontrol eder; uzak sonucu final commit üzerinde ayrıca doğrulanmalıdır.
- Yayın değişiklikleri bağımsız, salt okunur incelemeden geçti. Codex CLI wrapper çalışmadı: `Codex CLI with exec review is required`; mevcut terminalde uygun CLI yok.

Rector ve ayrı frontend lint/typecheck scripti projede yoktur. Belgeler düz Markdown olduğundan docs build komutu yoktur; bağlantılar dosya yollarına göre kontrol edilir. PHP/Laravel matrisi bu turda yerelde tekrarlanmadı. MySQL/PostgreSQL, yük testi ve PDF raster görsel doğrulaması yapılmadı. GitHub/Packagist yayın durumu için DURUM.md ve son handoff esas alınır.
