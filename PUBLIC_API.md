# Public API

Sürümleme sözleşmesi:
- BayPdf\BayPdfServiceProvider
- Contracts\ScopeResolver::resolve
- DocumentTypes::register, get, all, examples
- TemplateManager::create, save, publish, cloneDraft, preview, render
- PdfRenderer::render
- Assets::store, bytes, all, adoptLegacy
- DocumentValidator::validate, dimensions, blank
- VariableResolver::resolve
- Models\Template ve Models\TemplateVersion alanları/ilişkileri
- config/baypdf.php anahtarları
- baypdf-config ve baypdf-assets publish tag'leri
- baypdf_templates ve baypdf_versions şemaları

`ScopeResolver` host tarafından bind edilen opaque server-side scope contract'ıdır. `scoping.enabled=false` mevcut shared davranışı korur. Etkin modda resolver geçerli bir scope döndürmezse paket fail-closed davranır. `baypdf_templates.scope_key` nullable ve additive'dir; version scope'u template ilişkisinden türetilir.

`DocumentTypes::register` collection variable kabul eder. Collection schema ve resolved row contract'ı [değişken rehberinde](https://github.com/BayTekSis/bayPdf/blob/master/docs/VARIABLES.md) tanımlıdır. Mevcut scalar variable tanımları değişmeden geçerlidir.

Layout JSON iki contract taşır: schema version alanı olmayan legacy fixed-page belge ve açık `schema_version: 2` flow belgesi. V2 collection table, page repeat/context ve trailing block contract'ı [şablon rehberinde](https://github.com/BayTekSis/bayPdf/blob/master/docs/TEMPLATES.md) tanımlıdır. Legacy JSON otomatik rewrite edilmez.

Rendering ve Http namespace'leri iç uygulamadır. Vue kaynak bileşeni ayrı npm API değildir. JSON alanları [şablon rehberinde](https://github.com/BayTekSis/bayPdf/blob/master/docs/TEMPLATES.md) tanımlıdır. Model yazımı için TemplateManager kullanın; bulk SQL korumaları atlar.

İlk yayın öncesi API kararlı sürüm olarak duyurulmuş değildir.
