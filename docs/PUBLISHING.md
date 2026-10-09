# GitHub ve Composer yayını

Kaynak repository: [BayTekSis/bayPdf](https://github.com/BayTekSis/bayPdf), public; yayın branch'i `master`. Paket adı `bay/baypdf`, lisans MIT, telif sahibi BayPass'tır. Packagist kaydı ve vendor erişimi ayrıca doğrulanmalıdır; GitHub hesabı bu erişimi otomatik sağlamaz.

## Yayın öncesi

BayPdf kökünde:

```bash
composer qa
npm ci
npm test
npm run build
composer workbench:prepare
npx playwright install chromium
npm run test:browser
php vendor/bin/boost sync --check
git diff --check
composer archive --format=zip --dir=.artifacts --file=baypdf-dist
```

Windows PowerShell'de `npm.cmd` / `npx.cmd` kullanın. Tarayıcıları repository içinde tutmak için indirme ve testten önce `$env:PLAYWRIGHT_BROWSERS_PATH = "$PWD/.artifacts/playwright"` ayarlayın.

Arşiv runtime kaynaklarını, migration/config, Blade, çevirileri, derlenmiş JS/CSS/fontları ve lisans bildirimlerini içerir. Geliştirme dosyaları `.gitattributes` ile dışlanır. CI dağıtım arşivini açıp `composer install --no-dev` ve autoload/asset kontrolünü çalıştırır.

Kontrollerden sonra yalnızca ilgili dosyaları commit edin ve `git push origin master` çalıştırın. [Actions](https://github.com/BayTekSis/bayPdf/actions) üzerinde **aynı commit** için tüm işler başarılı olmalıdır. Eski commit'in yeşil olması yeterli değildir.

## Güvenlik ve erişim

Repository Settings → Security → Private vulnerability reporting etkinleştirilmelidir. [SECURITY.md](../SECURITY.md) özel bildirim kanalını ve destek kapsamını açıklar. GitHub ve Packagist kimlik doğrulamasını yerel araçların güvenli oturumlarıyla yapın; tokenları source code veya release notlarına koymayın.

## Mevcut etiket ve sonraki yayın

`0.1.0` etiketi repository'de vardır ve legacy tek sayfa/shared davranışı içerir. Scope ve schema v2 geliştirme branch'indedir; bu eklemeler için henüz yeni sürüm seçilmedi. [Public API](../PUBLIC_API.md) ve [kurulum/güncelleme adımları](INSTALLATION.md) güncel geliştirme sözleşmesini açıklar. `0.x` sürümlerinde uyumluluğu sürüm notlarından kontrol edin.

Package Boost kuralı gereği agent tag/release oluşturmaz. CI yeşil olduktan sonra seçilen sürüm için `internal/release-notes-<version>.md` hazırlanır; ilk satır gerçek, doğrulanmış commit SHA'sını içerir. `internal/` Git ve dağıtım dışında tutulur. Eski `release-notes-0.1.0.md` önceki yayının kaydıdır; yeni çalışma için yeniden kullanılmaz.

Yayınlamadan hemen önce:

1. Notların SHA'sını `git rev-parse HEAD` ve `git ls-remote origin refs/heads/master` ile karşılaştırın; üçü aynı olmalı.
2. Actions'ta o SHA için tüm test, kalite, tarayıcı ve dağıtım işleri tamamlanmış ve başarılı olmalı.
3. Seçilen yeni tag'in daha önce oluşturulmadığını kontrol edin; mevcut `0.1.0` tag'ini taşımayın.
4. GitHub Releases → Draft a new release ile seçilen yeni tag'i **master** üzerinde oluşturun. Başlık `v<version>`, gövde doğrulanmış notlar olmalı.
5. Yayın sonrası tag testlerini ve `Update changelog` işini izleyin. `CHANGELOG.md` release gövdesinden otomatik güncellenir.

GitHub CLI ile yayın komutu yalnızca SHA ve CI kontrolleri geçtikten sonra hazırlanmış handoff'tan çalıştırılır; hedef branch açıkça belirtilir.

## Packagist

1. [Packagist](https://packagist.org)'te paket sahibi hesabıyla oturum açın ve `bay` vendor adına erişimi doğrulayın. Ad değişirse composer.json, lockfile ve kurulum örnekleri birlikte güncellenmelidir.
2. Submit sayfasına `https://github.com/BayTekSis/bayPdf` adresini verin. Paket adı composer.json içinden okunur.
3. GitHub entegrasyonunu/webhook'u etkinleştirin; tag sonrası `0.1.0` sürümünün Packagist'te göründüğünü kontrol edin.
4. Temiz bir Laravel uygulamasında `composer require bay/baypdf:^0.1` ile kurulumu doğrulayın; config/assets publish ve migration adımlarını [kurulum rehberine](INSTALLATION.md) göre uygulayın.

Packagist yayını öncesinde Composer VCS repository üzerinden `dev-master` kurulabilir. npm yayını yapılmaz; tasarımcının derlenmiş dosyaları Composer paketindedir.
