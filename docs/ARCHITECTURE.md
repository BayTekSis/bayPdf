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

Tasarımcı Vue ile tema bağımsız oluşturuldu; aynı DejaVu fontları tarayıcı ve PDF tarafında kullanılıyor. Browser canvas yerleşim yardımcısıdır; nihai biçim ve QR için server PDF önizlemesi kullanılır. Composer varlıkları public/vendor/baypdf altına yayımlanır. Workbench yalnızca loopback isteklerde geliştirme kullanıcısı sağlar; bu kod dağıtım arşivinden çıkarılır.
