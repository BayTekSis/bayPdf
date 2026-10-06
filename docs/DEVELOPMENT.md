# Geliştirme

```bash
composer install
composer sync-ai
npm ci
npm run build
composer workbench:prepare
composer serve
```

Workbench 127.0.0.1:8127 üzerinde çalışır. SQLite/storage workbench altındadır. Hazırlama komutu mevcut DB'yi silmez; eksik dosyayı oluşturup migration çalıştırır.

## Skills

sandermuller/package-boost-laravel geliştirme bağımlılığıdır. sandermuller/boost-skills README ve kalite skills sağlar. boost.php yalnızca Codex ve ilgili vendor/tag'leri seçer.

```bash
composer sync-ai
php vendor/bin/boost sync --check
php vendor/bin/boost doctor
```

Kurallar .ai/guidelines/baypdf.md'den AGENTS.md'ye üretilir. Skills .agents/skills altında gitignore'dadır; sync ile yeniden oluşturulur. Dev bağımlılıklar tüketici uygulamaya taşınmaz. laravel/boost MCP kurulumu zorunlu değildir.

Uygulanan rehberler: package-development, cross-version-laravel-support, lean-dist, readme, backend-quality; matris sorunlarında ci-matrix-troubleshooting. Tasarımcı için frontend-design kullanıldı.

## Kontroller

```bash
composer qa
npm test
npm run build
composer workbench:prepare
npx playwright install chromium
npm run test:browser
```

QA: manifest, Pint, Larastan/PHPStan level 5, PHPUnit ve dağıtım kontrolü. Backend SQLite in-memory, browser testleri yalnızca workbench kullanır. CI PHP 8.3/8.4, Laravel 12/13 ve prefer-lowest/prefer-stable matrisini tanımlar.

Paket kökünde php artisan yerine php vendor/bin/testbench kullanılır. Host uygulamaya kurulumda normal artisan geçerlidir.

Frontend kaynakları resources/js, dağıtılan build public altındadır. Build kaynakla birlikte commit edilir. npm run dev build-watch çalıştırır. Workbench'e aktarmak için:

```bash
php vendor/bin/testbench vendor:publish --tag=baypdf-assets --force
```

## İzole Composer ortamı

Global GitHub auth ayarı public indirmeleri bozarsa BayPdf kökünde PowerShell:

```powershell
$env:COMPOSER_HOME = Join-Path (Get-Location) '.artifacts/composer-home'
$env:COMPOSER_CACHE_DIR = Join-Path (Get-Location) '.artifacts/composer-cache'
Remove-Item Env:COMPOSER_AUTH -ErrorAction SilentlyContinue
composer install
```

Global Composer dosyaları değişmez; sadece süreç ortamı belirlenir. Özel paketler için uygun kimlik doğrulaması gerekir.

Windows'ta Package Boost lean wrapper'ı uzantısız proxy'yi executable olarak tanımayabilir. composer dist:check aynı validator'ı doğrudan PHP ile çalıştırır.
