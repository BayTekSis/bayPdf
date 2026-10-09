# Güvenlik sınırları

Tasarımcı varsayılan kapalıdır. Host authentication ve Gate gerektirir. BayPdf kendi kullanıcı/rol tablosunu oluşturmaz. API ve özel görseller aynı yetki kontrolünden geçer.

Varsayılan mod tek ortak şablon alanıdır. İzolasyon gereken host'lar `scoping.enabled=true` ayarlayıp `BayPdf\Contracts\ScopeResolver` bind eder. Scope yalnız server-side current context'ten çözülür; request body/query içindeki scope alanları authoritative değildir. Resolver boş/geçersiz dönerse erişim fail-closed durur.

Scoped modda template ve version route binding, listeleme ve `TemplateManager` lifecycle/render çağrıları current scope ile sınırlandırılır. Başka scope'a ait tahmin edilmiş ID'ler bulunamaz. Version scope'u canonical template ilişkisinden gelir ve version başka template'e taşınamaz. Direct raw Eloquent/SQL sorguları tamamen engellenemez; host entegrasyonu okuma/yazma/render için `TemplateManager` ve designer API yolunu kullanmalıdır.

Migration sonrası eski `scope_key=null` template'ler scoped modda görünmez. Ownership kanıtlanmadan topluca bir current scope'a atanmaz; açık ID eşlemesi host migration'ında yapılır.

Host render çağrısına verdiği verilerin erişimini kendi doğrular. PHP servisleri host kodunu güvenilir kabul eder. PDF otomatik depolanmaz, e-postalanmaz veya loglanmaz.

## Dosyalar

- PNG/JPEG dışında upload reddedilir.
- Byte ve piksel sınırları yüklemede/okumada uygulanır.
- Assetler yapılandırılmış önekle sınırlıdır; traversal, URL ve stream wrapper yolları reddedilir.
- Upload'lar UUID adlarıyla, private görünürlük isteğiyle saklanır.
- Host diskinin kökü HTTP üzerinden sunulmamalıdır. Windows chmod bitleri güvenlik sınırı değildir.
- Font cache ve geçici görseller özel yerel alandadır. Vendor fontlarına yazılmaz.
- QR içeriği indirilmez, yalnızca kodlanır. Render sırasında harici HTTP isteği yapılmaz.
- Otomatik asset temizliği yoktur. Bütün sürüm referanslarını kontrol etmeden dosya silmeyin.

SVG/HTML/PHP çalıştırılabilir içerik olarak işlenmez. Tasarımcı metinleri Vue interpolasyonu, başlangıç verileri escaped Blade attribute ile gösterilir. PDF önizlemesi private,no-store döner.

## Workbench

Workbench yalnızca geliştirme içindir. Loopback'ten gelen isteklerde geliştirme kullanıcısı sağlar; internete veya reverse proxy arkasına yayınlamayın. Workbench dağıtım arşivinden çıkarılır.

Özel bildirim yolu ve destek kapsamı [SECURITY.md](../SECURITY.md) içinde tanımlıdır. Repository ayarlarından private vulnerability reporting etkinleştirilmelidir. Kamuya açık issue ve fixture içine hassas veri/token eklemeyin.
