# Güvenlik sınırları

Tasarımcı varsayılan kapalıdır. Host authentication ve Gate gerektirir. BayPdf kendi kullanıcı/rol tablosunu oluşturmaz. API ve özel görseller aynı yetki kontrolünden geçer.

İlk sürüm tek ortak şablon alanıdır. Gate'i geçen yöneticiler bütün şablon ve assetleri görebilir. Yerleşik tenant izolasyonu yoktur; route'ları tenant müşterilerine açmayın.

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

Repository açıldıktan sonra GitHub private vulnerability reporting etkinleştirilmelidir. Kamuya açık issue ve fixture içine hassas veri/token eklemeyin.
