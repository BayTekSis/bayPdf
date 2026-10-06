# GitHub ve dağıtım

Remote, repository sahibi/görünürlüğü ve lisans henüz seçilmemiştir. bay/baypdf geliştirme adı Packagist sahipliği sağlamaz.

GitHub'da boş repository oluşturun; GitHub tarafında README/.gitignore eklemeyin. BayPdf kökünde:

```powershell
$repositoryUrl = Read-Host 'Yeni GitHub repository HTTPS adresi'
git remote add origin $repositoryUrl
git push -u origin HEAD
```

Bu kaynakları yükler; Composer yayını yapmaz. Tokenları dosyalara yazmayın.

## Yayın öncesi

```bash
composer qa
npm ci
npm test
npm run build
composer workbench:prepare
npm run test:browser
git diff --check
composer archive --format=zip --dir=.artifacts
```

GitHub CI de yeşil olmalıdır. Lisans seçildiğinde LICENSE, composer.json ve README birlikte güncellenir. Yerel başarı GitHub CI sonucu olarak raporlanmaz.

Arşiv PHP, config, migration, Blade, çeviri, derlenmiş JS/CSS/fontlar ve lisans bildirimlerini içerir. Tests/workbench/skills/CI/frontend kaynakları/lockfile geliştirme içindir ve çıkarılır.

## Sürümleme

[PUBLIC_API.md](../PUBLIC_API.md) sözleşmesine göre kırıcı değişiklik major, özellik minor, hata düzeltmesi patch'tir. İlk deneme sürümü için 0.1.0 uygundur.

Package Boost kuralları gereği yayın öncesi handoff yapılır; tag/release kullanıcı tarafından tamamlanır. Bütün kontroller ve lisans kararı tamamlandıktan sonra:

```bash
git tag -a 0.1.0 -m "BayPdf 0.1.0"
git push origin 0.1.0
```

Release başlığı v0.1.0, tag 0.1.0 olur. Notlar gerçek yeşil commit'e bağlı hazırlanır. Release gövdesi update-changelog workflow'u ile CHANGELOG.md'ye eklenir.

## Packagist / npm

Herkese açık dağıtımda seçilen vendor/paket adı composer.json'a yazılır; GitHub repository Packagist'e kaydedilir ve webhook etkinleştirilir. Packagist Git etiketlerini okur.

Özel dağıtımda host composer.json içinde vcs repository kullanılır; tüketici hesabının erişimi gerekir. Packagist zorunlu değildir.

npm yayını gerekmez. package.json private'tır; derlenmiş tasarımcı Composer arşivinde gelir. Ayrı Vue npm bileşeni bu sürümde yoktur.
