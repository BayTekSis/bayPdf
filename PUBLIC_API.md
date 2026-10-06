# Public API

Sürümleme sözleşmesi:
- BayPdf\BayPdfServiceProvider
- DocumentTypes::register, get, all, examples
- TemplateManager::create, save, publish, cloneDraft, preview, render
- PdfRenderer::render
- Assets::store, bytes
- DocumentValidator::validate, dimensions, blank
- VariableResolver::resolve
- Models\Template ve Models\TemplateVersion alanları/ilişkileri
- config/baypdf.php anahtarları
- baypdf-config ve baypdf-assets publish tag'leri
- baypdf_templates ve baypdf_versions şemaları

Rendering ve Http namespace'leri iç uygulamadır. Vue kaynak bileşeni ayrı npm API değildir. JSON alanları docs/TEMPLATES.md'de tanımlıdır. Model yazımı için TemplateManager kullanın; bulk SQL korumaları atlar.

İlk yayın öncesi API kararlı sürüm olarak duyurulmuş değildir.
