# Mimari kararlar

BayPdf, Laravel 12/13 ve PHP 8.3+ hedefleyen Composer paketidir. Jugend ile çalışma zamanı bağlantısı yoktur.

- Şablon sürümleri JSON belge tanımı saklar; renderer bu tanımı ve host verisini alır.
- Belge türleri ve değişkenler host servis sağlayıcısında kaydedilir.
- Yayınlanmış sürümler değişmez; değişiklik için yeni taslak oluşturulur.
- Yerleşim milimetre, metin boyutu punto cinsindedir.
- FPDF ailesindeki tFPDF ile Unicode çıktı ve endroid ile QR üretilir.
- İlk sürüm sabit sayfa yerleşimi kullanır; taşan içerik anlaşılır hatayla reddedilir.
- Görseller izin verilen özel storage önekiyle sınırlandırılır.
- Tasarımcı kendi derlenmiş Vue varlıklarıyla Composer arşivinde yer alır.
- Workbench ve örnek veriler yalnızca geliştirme içindir, dağıtım arşivine girmez.
- Yetkilendirme host authentication + manage-baypdf Gate ile sağlanır. Shared alan varsayılandır; opt-in scoped mod authoritative opaque anahtarı host'un server-side `ScopeResolver` contract'ından alır.

Değişken şeması her sürümde snapshot olarak saklanır. Sonradan registry değişmesi eski sürümü etkilemez. Taslak klonlama snapshot'ı korur; yeni şema için yeni şablon açılır. Eşzamanlı düzenlemeler lock_version ile 409 döndürür. Fontlar vendor içine yazılmadan özel cache alanında hazırlanır. Üretim için yayınlanmış sürüm zorunludur; önizleme örnek verileri kullanır.

Scope ownership `baypdf_templates.scope_key` alanında tutulur. Version ownership canonical template ilişkisinden türetilir; duplicate scope kaynağı yoktur. Nullable alan eski shared satırları korur. Scoped mod null legacy satırları görünmez tutar ve resolver olmadığında fail-closed davranır. Browser scope seçemez; model route binding ve TemplateManager aynı scope context'i uygular.

Asset scope'u storage path ile taşınır: `asset_prefix/scopes/<sha256(scope)>/<uuid>`. Ham opaque scope path'e yazılmaz. Legacy key adoption, byte'ları current fingerprint altında mirror ederek immutable published JSON key'ini korur. Asset registry/tablosu yoktur; catalog yalnız current prefix'i listeler ve deletion bilinçli olarak sunulmaz.

Collection variable schema, mevcut scalar variable listesinde `type=collection` ve flat scalar `fields` ile temsil edilir. Resolver list/record shape'i ve resource limitlerini doğrulayıp date/number/money değerlerini table renderer'dan önce deterministically formatlar. Example rows version schema snapshot'ına dahildir. Nested collection ve rich content engine kapsam dışıdır.

Layout schema v2, legacy absolute JSON'dan açıkça ayrılır. Renderer önce font metrikleriyle table header, wrapped row ve trailing block planını çıkarır; toplam sayfa sayısı bu immutable planın uzunluğudur. PDF tek çizim geçişinde üretilir. Page context placeholder'ı PDF byte replacement değildir. Same layout/data/config aynı pagination structure'ını üretir; tFPDF creation timestamp metadata'sı byte-for-byte identity garantisini sınırlar.

Bir v2 flow tek primary collection table taşır. Row page'e bölünmez; bütün columns aynı measured height'i kullanır. Table devam ederken header isteğe bağlı tekrar eder. Trailing blocks gerçek table bitişini izler ve bütün olarak sonraki page'e taşınır. Absolute page elements repeat kurallarıyla header/footer ve first/continuation/last içerik sağlar.

Tasarımcı Vue ile tema bağımsız oluşturuldu; aynı DejaVu fontları tarayıcı ve PDF tarafında kullanılıyor. Browser canvas yerleşim yardımcısıdır; nihai biçim ve QR için server PDF önizlemesi kullanılır. Composer varlıkları public/vendor/baypdf altına yayımlanır. Workbench yalnızca loopback isteklerde geliştirme kullanıcısı sağlar; bu kod dağıtım arşivinden çıkarılır.
